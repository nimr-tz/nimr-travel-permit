<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalChainService $chainService) {}

    public function store(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $user = $request->user();

        // Only the current approver can act
        abort_unless($travelRequest->current_approver_id === $user->id, 403);
        abort_unless($travelRequest->status === 'pending', 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        // Determine which stage this approver is acting at
        $chain = $travelRequest->approval_chain;
        $step  = collect($chain)->firstWhere('approver_id', $user->id);
        $stage = $step['stage'] ?? 'supervisor';

        // Record the action
        ApprovalAction::create([
            'travel_request_id' => $travelRequest->id,
            'actor_id'          => $user->id,
            'stage'             => $stage,
            'decision'          => $validated['decision'],
            'comment'           => $validated['comment'] ?? null,
            'acted_at'          => now(),
        ]);

        // Advance (or reject) the request
        $this->chainService->advance($travelRequest, $validated['decision']);

        $message = $validated['decision'] === 'approved'
            ? 'Umeidhinisha ombi hili.'
            : 'Umekataa ombi hili.';

        return redirect()->route('travel-requests.show', $travelRequest)->with('status', $message);
    }
}
