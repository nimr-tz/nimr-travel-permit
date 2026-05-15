<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TravelRequestController extends Controller
{
    public function __construct(private ApprovalChainService $chainService) {}

    public function index(): View
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

        $requests = $query->latest()->get();

        return view('travel-requests.index', compact('requests', 'user'));
    }

    public function create(): View
    {
        $user = auth()->user();
        return view('travel-requests.create', compact('user'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            // Section B
            'b_applicant_name'           => ['required', 'string', 'max:255'],
            'b_phone'                    => ['nullable', 'string', 'max:50'],
            'b_email'                    => ['nullable', 'email', 'max:255'],
            'b_position'                 => ['nullable', 'string', 'max:255'],
            'b_destination'              => ['required', 'string', 'max:500'],
            'b_departure_date'           => ['required', 'date'],
            'b_return_date'              => ['required', 'date', 'after_or_equal:b_departure_date'],
            // Section C
            'c_travel_source'            => ['nullable', 'string'],
            // Section D
            'd_benefit_to_institution'   => ['nullable', 'string'],
            'd_benefit_to_nation'        => ['nullable', 'string'],
            'd_consequences_if_rejected' => ['nullable', 'string'],
            // Section E
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
            // Section F
            'f_previous_travel_impact'   => ['nullable', 'string'],
            // Section G
            'g_handover_officer_name'    => ['nullable', 'string', 'max:255'],
            'g_handover_officer_title'   => ['nullable', 'string', 'max:255'],
            'g_handover_document'        => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
        ]);

        $user      = $request->user();
        $isDraft   = $request->input('action') === 'draft';

        $chain             = null;
        $currentApproverId = null;
        $status            = 'draft';
        $submittedAt       = null;

        if (!$isDraft) {
            try {
                $chain             = $this->chainService->buildChain($user);
                $currentApproverId = $chain[0]['approver_id'];
                $status            = 'pending';
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
            'request_number'       => $this->nextRequestNumber(),
            'requester_id'         => $user->id,
            'unit_id'              => $user->unit_id,
            'status'               => $status,
            'approval_chain'       => $chain,
            'current_approver_id'  => $currentApproverId,
            'submitted_at'         => $submittedAt,
        ]);

        $message = $isDraft ? 'Ombi limehifadhiwa kama rasimu.' : 'Ombi limewasilishwa kwa mafanikio.';

        return redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);
    }

    public function show(TravelRequest $travelRequest): View
    {
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);
        return view('travel-requests.show', compact('travelRequest'));
    }

    public function edit(TravelRequest $travelRequest): View
    {
        abort_unless($travelRequest->status === 'draft', 403);
        abort_unless($travelRequest->requester_id === auth()->id(), 403);
        $user = auth()->user();
        return view('travel-requests.edit', compact('travelRequest', 'user'));
    }

    public function update(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        abort_unless($travelRequest->status === 'draft', 403);
        abort_unless($travelRequest->requester_id === auth()->id(), 403);

        $validated = $request->validate([
            'b_applicant_name'           => ['required', 'string', 'max:255'],
            'b_phone'                    => ['nullable', 'string', 'max:50'],
            'b_email'                    => ['nullable', 'email', 'max:255'],
            'b_position'                 => ['nullable', 'string', 'max:255'],
            'b_destination'              => ['required', 'string', 'max:500'],
            'b_departure_date'           => ['required', 'date'],
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
        ]);

        $isDraft = $request->input('action') === 'draft';

        if (!$isDraft) {
            try {
                $chain = $this->chainService->buildChain($request->user());
                $travelRequest->update([
                    ...$validated,
                    'status'              => 'pending',
                    'approval_chain'      => $chain,
                    'current_approver_id' => $chain[0]['approver_id'],
                    'submitted_at'        => now(),
                ]);
            } catch (\RuntimeException $e) {
                return back()->withInput()->withErrors(['submit' => $e->getMessage()]);
            }
        } else {
            $travelRequest->update($validated);
        }

        $message = $isDraft ? 'Rasimu imesasishwa.' : 'Ombi limewasilishwa kwa mafanikio.';
        return redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);
    }

    public function print(TravelRequest $travelRequest): \Illuminate\View\View
    {
        $travelRequest->load(['requester', 'unit', 'currentApprover', 'approvalActions.actor']);
        return view('travel-requests.print', compact('travelRequest'));
    }

    protected function nextRequestNumber(): string
    {
        $last = TravelRequest::max('id') ?? 0;
        return 'NIMR-ITP-' . now()->format('Y') . '-' . Str::padLeft((string) ($last + 1), 3, '0');
    }
}
