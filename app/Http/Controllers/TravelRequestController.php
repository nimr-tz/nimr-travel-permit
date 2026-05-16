<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Notifications\TravelRequestApprovedNotification;
use App\Notifications\TravelRequestRejectedNotification;
use App\Notifications\TravelRequestReturnedNotification;
use App\Notifications\TravelRequestSubmittedNotification;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            // no filter — sees all
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

        $travelRequest = TravelRequest::create([
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

        if (!$isDraft && $chain) {
            $this->notifyFirstApprover($travelRequest);
        }

        $message = $isDraft ? 'Ombi limehifadhiwa kama rasimu.' : 'Ombi limewasilishwa kwa mafanikio.';
        return redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);
    }

    public function show(TravelRequest $travelRequest): View
    {
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
        abort_unless($travelRequest->isEditable(), 403);
        abort_unless($travelRequest->requester_id === auth()->id(), 403);
        $user = auth()->user();
        return view('travel-requests.edit', compact('travelRequest', 'user'));
    }

    public function update(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        abort_unless($travelRequest->isEditable(), 403);
        abort_unless($travelRequest->requester_id === auth()->id(), 403);

        $validated = $this->validateForm($request, withFile: false);
        $isDraft   = $request->input('action') === 'draft';

        if (!$isDraft) {
            try {
                $chain = $this->chainService->buildChain($request->user());
                $travelRequest->update([
                    ...$validated,
                    'status'              => TravelRequest::STATUS_PENDING,
                    'approval_chain'      => $chain,
                    'current_approver_id' => $chain[0]['approver_id'],
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

        $message = $isDraft ? 'Rasimu imesasishwa.' : 'Ombi limewasilishwa kwa mafanikio.';
        return redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);
    }

    public function cancel(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        abort_unless($travelRequest->requester_id === auth()->id(), 403);
        abort_unless($travelRequest->isCancellable(), 403);

        $travelRequest->update([
            'status'              => TravelRequest::STATUS_CANCELLED,
            'current_approver_id' => null,
        ]);

        return redirect()->route('travel-requests.show', $travelRequest)
            ->with('status', 'Ombi limefutwa.');
    }

    public function download(TravelRequest $travelRequest)
    {
        abort_unless(
            auth()->id() === $travelRequest->requester_id
            || auth()->id() === $travelRequest->current_approver_id
            || auth()->user()->isHr()
            || auth()->user()->isDirectorGeneral()
            || $travelRequest->approvalActions()->where('actor_id', auth()->id())->exists(),
            403
        );

        abort_unless($travelRequest->g_handover_document, 404);
        abort_unless(Storage::disk('private')->exists($travelRequest->g_handover_document), 404);

        return Storage::disk('private')->download($travelRequest->g_handover_document);
    }

    public function print(TravelRequest $travelRequest): View
    {
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);
        return view('travel-requests.print', compact('travelRequest'));
    }

    protected function validateForm(Request $request, bool $withFile = true): array
    {
        $rules = [
            'b_applicant_name'           => ['required', 'string', 'max:255'],
            'b_phone'                    => ['nullable', 'string', 'max:50'],
            'b_email'                    => ['nullable', 'email', 'max:255'],
            'b_position'                 => ['nullable', 'string', 'max:255'],
            'b_destination'              => ['required', 'string', 'max:500'],
            'b_departure_date'           => ['required', 'date', 'after_or_equal:today'],
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

    protected function nextRequestNumber(): string
    {
        $year = now()->year;
        $last = TravelRequest::whereYear('created_at', $year)->max('id') ?? 0;
        $seq  = TravelRequest::whereYear('created_at', $year)->count() + 1;
        return 'NIMR-ITP-' . $year . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    private function notifyFirstApprover(TravelRequest $travelRequest): void
    {
        try {
            $firstApprover = \App\Models\User::find($travelRequest->current_approver_id);
            if ($firstApprover) {
                $firstApprover->notify(new TravelRequestSubmittedNotification($travelRequest));
            }
        } catch (\Throwable) {
            // Notification failure must never break the main flow
        }
    }
}
