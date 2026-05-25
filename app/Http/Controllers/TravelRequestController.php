<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestHrCopyNotification;
use App\Notifications\TravelRequestSubmittedNotification;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TravelRequestController extends Controller
{
    public function __construct(private ApprovalChainService $chainService) {}

    public function index(Request $request): View
    {
        $user  = auth()->user();
        $query = TravelRequest::with(['requester', 'unit', 'currentApprover']);

        if ($user->isHr()) {
            if ($user->unit?->type === 'research_centre') {
                $query->where('unit_id', $user->unit_id);
            }
        } elseif ($user->isDirectorGeneral()) {
            // DG sees: pending requests at their stage + all resolved/returned requests.
            // They do NOT see drafts or requests still pending at a lower stage.
            $query->where(function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('status', TravelRequest::STATUS_PENDING)
                          ->where('current_approver_id', $user->id);
                })->orWhereIn('status', [
                    TravelRequest::STATUS_APPROVED,
                    TravelRequest::STATUS_REJECTED,
                    TravelRequest::STATUS_RETURNED,
                    TravelRequest::STATUS_CANCELLED,
                ]);
            });
        } else {
            $query->where('requester_id', $user->id);
        }

        // Clone base query for status counts (before search/status filters)
        $baseQuery = clone $query;

        // Search
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('b_applicant_name', 'like', "%{$search}%")
                  ->orWhere('b_destination', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%");
            });
            $baseQuery->where(function ($q) use ($search) {
                $q->where('b_applicant_name', 'like', "%{$search}%")
                  ->orWhere('b_destination', 'like', "%{$search}%")
                  ->orWhere('request_number', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->get('status')) {
            if (in_array($status, TravelRequest::STATUSES)) {
                $query->where('status', $status);
            }
        }

        $requests     = $query->latest()->paginate(15)->withQueryString();
        $statusCounts = $baseQuery->selectRaw('status, count(*) as cnt')
                                  ->groupBy('status')
                                  ->pluck('cnt', 'status')
                                  ->toArray();

        return view('travel-requests.index', compact('requests', 'user', 'statusCounts'));
    }

    public function create(): View
    {
        $user = auth()->user();
        return view('travel-requests.create', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateForm($request);

        $user    = $request->user();
        $isDraft = $request->input('action') === 'draft';

        if (!$isDraft && $this->missingSupervisor($user)) {
            return redirect()->route('dashboard')
                ->with('status', __('dashboard.supervisor_required_to_submit'));
        }

        $chain             = null;
        $currentApproverId = null;
        $status            = TravelRequest::STATUS_DRAFT;
        $submittedAt       = null;

        if (!$isDraft) {
            try {
                $chain             = $this->chainService->buildChain($user);
                $currentApproverId = $chain[0]['approver_id'];
                $status            = TravelRequest::STATUS_PENDING;
                $submittedAt       = now();
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['submit' => $e->getMessage()]);
            }
        }

        $documentPath = null;
        if ($request->hasFile('g_handover_document')) {
            $documentPath = $request->file('g_handover_document')->store('handover-documents', 'private');
        }

        $travelRequest = DB::transaction(function () use ($validated, $documentPath, $user, $status, $chain, $currentApproverId, $submittedAt) {
            return TravelRequest::create([
                ...$validated,
                'g_handover_document' => $documentPath,
                'request_number'      => $this->nextRequestNumber(),
                'requester_id'        => $user->id,
                'unit_id'             => $user->unit_id,
                'status'              => $status,
                'approval_chain'      => $chain,
                'current_approver_id' => $currentApproverId,
                'submitted_at'        => $submittedAt,
            ]);
        });

        if (!$isDraft && $chain) {
            $this->notifyFirstApprover($travelRequest);
        }

        $message  = $isDraft ? 'Ombi limehifadhiwa kama rasimu.' : 'Ombi limewasilishwa kwa mafanikio.';
        $redirect = redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);

        if (!$isDraft) {
            $overlap = $this->findOverlappingRequest($user->id, $validated['b_departure_date'], $validated['b_return_date'], $travelRequest->id);
            if ($overlap) {
                $redirect->with('overlap_warning', $overlap->request_number);
            }
        }

        return $redirect;
    }

    public function show(TravelRequest $travelRequest): View
    {
        $this->authorize('view', $travelRequest);
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);

        // Preload all approvers from the chain to avoid N+1 queries in the view
        $chainApprovers = collect();
        if ($travelRequest->approval_chain) {
            $ids = collect($travelRequest->approval_chain)->pluck('approver_id')->filter()->unique();
            $chainApprovers = \App\Models\User::whereIn('id', $ids)->get()->keyBy('id');
        }

        return view('travel-requests.show', compact('travelRequest', 'chainApprovers'));
    }

    public function edit(TravelRequest $travelRequest): View
    {
        $this->authorize('update', $travelRequest);
        $user = auth()->user();
        return view('travel-requests.edit', compact('travelRequest', 'user'));
    }

    public function update(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $this->authorize('update', $travelRequest);

        $validated = $this->validateForm($request, withFile: true);
        $isDraft   = $request->input('action') === 'draft';

        if (!$isDraft && $this->missingSupervisor($request->user())) {
            return redirect()->route('dashboard')
                ->with('status', __('dashboard.supervisor_required_to_submit'));
        }

        // Handle file replacement
        if ($request->hasFile('g_handover_document')) {
            if ($travelRequest->g_handover_document) {
                Storage::disk('private')->delete($travelRequest->g_handover_document);
            }
            $validated['g_handover_document'] = $request->file('g_handover_document')
                ->store('handover-documents', 'private');
        } else {
            unset($validated['g_handover_document']);
        }

        if (!$isDraft) {
            try {
                // If resubmitting a returned request, resume from the approver who
                // returned it rather than restarting the whole chain from step 1.
                $wasReturned = $travelRequest->status === TravelRequest::STATUS_RETURNED;

                if ($wasReturned && $travelRequest->approval_chain) {
                    $chain = $travelRequest->approval_chain;
                    $returnAction = $travelRequest->approvalActions()
                        ->where('decision', 'returned')
                        ->latest('acted_at')
                        ->first();
                    $resumeIndex = $returnAction
                        ? collect($chain)->search(fn($step) => (int) $step['approver_id'] === (int) $returnAction->actor_id)
                        : false;
                    $startApprover = $resumeIndex !== false ? $chain[$resumeIndex]['approver_id'] : $chain[0]['approver_id'];
                } else {
                    $chain         = $this->chainService->buildChain($request->user());
                    $startApprover = $chain[0]['approver_id'];
                }

                $travelRequest->update([
                    ...$validated,
                    'status'              => TravelRequest::STATUS_PENDING,
                    'approval_chain'      => $chain,
                    'current_approver_id' => $startApprover,
                    'submitted_at'        => now(),
                ]);
                $this->notifyFirstApprover($travelRequest);
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['submit' => $e->getMessage()]);
            }
        } else {
            $travelRequest->update([
                ...$validated,
                'status' => TravelRequest::STATUS_DRAFT,
            ]);
        }

        $message  = $isDraft ? 'Rasimu imesasishwa.' : 'Ombi limewasilishwa kwa mafanikio.';
        $redirect = redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);

        if (!$isDraft) {
            $overlap = $this->findOverlappingRequest($request->user()->id, $validated['b_departure_date'], $validated['b_return_date'], $travelRequest->id);
            if ($overlap) {
                $redirect->with('overlap_warning', $overlap->request_number);
            }
        }

        return $redirect;
    }

    public function cancel(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $this->authorize('cancel', $travelRequest);

        $travelRequest->update([
            'status'              => TravelRequest::STATUS_CANCELLED,
            'current_approver_id' => null,
        ]);

        return redirect()->route('travel-requests.show', $travelRequest)
            ->with('status', 'Ombi limefutwa.');
    }

    public function download(TravelRequest $travelRequest)
    {
        $this->authorize('download', $travelRequest);

        abort_unless($travelRequest->g_handover_document, 404);
        abort_unless(Storage::disk('private')->exists($travelRequest->g_handover_document), 404);

        return Storage::disk('private')->download($travelRequest->g_handover_document);
    }

    public function print(TravelRequest $travelRequest): View
    {
        $this->authorize('view', $travelRequest);
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);
        return view('travel-requests.print', compact('travelRequest'));
    }

    public function pdf(TravelRequest $travelRequest)
    {
        $this->authorize('view', $travelRequest);
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('travel-requests.pdf', compact('travelRequest'))
            ->setPaper('a4', 'portrait');

        $filename = $travelRequest->request_number . '.pdf';

        return $pdf->download($filename);
    }

    protected function validateForm(Request $request, bool $withFile = true): array
    {
        $rules = [
            'b_applicant_name'           => ['required', 'string', 'max:255'],
            'b_phone'                    => ['nullable', 'string', 'max:50'],
            'b_email'                    => ['nullable', 'email', 'max:255'],
            'b_position'                 => ['nullable', 'string', 'max:255'],
            'b_destination'              => ['required', 'string', 'max:500'],
            'b_departure_date'           => ['required', 'date', 'after:today'],
            'b_return_date'              => ['required', 'date', 'after_or_equal:b_departure_date'],
            'c_travel_source'            => ['nullable', 'string'],
            'd_benefit_to_institution'   => ['nullable', 'string'],
            'd_benefit_to_nation'        => ['nullable', 'string'],
            'd_consequences_if_rejected' => ['nullable', 'string'],
            'e_transport_costs'          => ['nullable', 'string'],
            'e_allowance_a'              => ['nullable', 'string', 'max:500'],
            'e_allowance_b'              => ['nullable', 'string', 'max:500'],
            'e_allowance_c'              => ['nullable', 'string', 'max:500'],
            'e_allowance_d'              => ['nullable', 'string', 'max:500'],
            'e_budget_line'              => ['nullable', 'string', 'max:500'],
            'e_donor_cost_i'             => ['nullable', 'string', 'max:500'],
            'e_donor_cost_ii'            => ['nullable', 'string', 'max:500'],
            'e_donor_cost_iii'           => ['nullable', 'string', 'max:500'],
            'e_govt_cost_i'              => ['nullable', 'string', 'max:500'],
            'e_govt_cost_ii'             => ['nullable', 'string', 'max:500'],
            'e_govt_cost_iii'            => ['nullable', 'string', 'max:500'],
            'e_other_costs'              => ['nullable', 'string'],
            'f_previous_travel_impact'   => ['nullable', 'string'],
            'f_traveller_signed_date'    => ['nullable', 'date'],
            'g_handover_officer_name'    => ['nullable', 'string', 'max:255'],
            'g_handover_officer_title'   => ['nullable', 'string', 'max:255'],
        ];

        if ($withFile) {
            $rules['g_handover_document'] = ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'];
        }

        return $request->validate($rules);
    }

    private function missingSupervisor(User $user): bool
    {
        $user->loadMissing('unit');
        return $user->role === 'staff'
            && $user->unit_id !== null
            && !$user->supervisor_id;
    }

    private function findOverlappingRequest(int $userId, string $departure, string $return, int $excludeId): ?TravelRequest
    {
        return TravelRequest::where('requester_id', $userId)
            ->where('id', '!=', $excludeId)
            ->whereIn('status', [TravelRequest::STATUS_DRAFT, TravelRequest::STATUS_PENDING, TravelRequest::STATUS_APPROVED, TravelRequest::STATUS_RETURNED])
            ->where('b_departure_date', '<=', $return)
            ->where('b_return_date', '>=', $departure)
            ->first();
    }

    protected function nextRequestNumber(): string
    {
        $year = now()->year;
        $seq  = TravelRequest::whereYear('created_at', $year)->lockForUpdate()->count() + 1;
        return 'NIMR-ITP-' . $year . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function notifyFirstApprover(TravelRequest $travelRequest): void
    {
        try {
            $firstApprover = \App\Models\User::find($travelRequest->current_approver_id);
            if ($firstApprover) {
                $firstApprover->notify(new TravelRequestSubmittedNotification($travelRequest));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify first approver for request ' . $travelRequest->request_number, [
                'approver_id' => $travelRequest->current_approver_id,
                'error'       => $e->getMessage(),
            ]);
        }

        // Send HR an awareness copy; HR does not approve the request.
        try {
            $this->chainService
                ->hrCopyRecipients($travelRequest)
                ->each(fn($hr) => $hr->notify(new TravelRequestHrCopyNotification($travelRequest, 'submitted')));
        } catch (\Throwable $e) {
            Log::warning('Failed to send HR copy on submission for request ' . $travelRequest->request_number, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
