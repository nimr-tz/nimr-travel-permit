<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Notifications\TravelRequestApprovedNotification;
use App\Notifications\TravelRequestRejectedNotification;
use App\Notifications\TravelRequestReturnedNotification;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalChainService $chainService) {}

    public function store(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int)$travelRequest->current_approver_id === (int)$user->id, 403);
        abort_unless($travelRequest->status === TravelRequest::STATUS_PENDING, 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,returned'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['decision'] === 'returned') {
            $request->validate(['comment' => ['required', 'string', 'min:10', 'max:2000']]);
        }

        $chain = $travelRequest->approval_chain;
        $step  = collect($chain)->firstWhere('approver_id', $user->id);
        $stage = $step['stage'] ?? 'supervisor';

        ApprovalAction::create([
            'travel_request_id' => $travelRequest->id,
            'actor_id'          => $user->id,
            'stage'             => $stage,
            'decision'          => $validated['decision'],
            'comment'           => $validated['comment'] ?? null,
            'acted_at'          => now(),
        ]);

        $this->chainService->advance($travelRequest, $validated['decision']);

        $travelRequest->refresh();

        $this->sendNotification($travelRequest, $validated['decision']);

        $messages = [
            'approved' => 'Umeidhinisha ombi hili.',
            'rejected' => 'Umekataa ombi hili.',
            'returned' => 'Ombi limerudishwa kwa mwombaji kwa marekebisho.',
        ];

        return redirect()
            ->route('travel-requests.show', $travelRequest)
            ->with('status', $messages[$validated['decision']]);
    }

    private function sendNotification(TravelRequest $travelRequest, string $decision): void
    {
        try {
            $requester = $travelRequest->requester;
            if (!$requester) {
                return;
            }

            match ($decision) {
                'approved' => $travelRequest->status === TravelRequest::STATUS_APPROVED
                    ? $requester->notify(new TravelRequestApprovedNotification($travelRequest))
                    : null,
                'rejected' => $requester->notify(new TravelRequestRejectedNotification($travelRequest)),
                'returned' => $requester->notify(new TravelRequestReturnedNotification($travelRequest)),
                default    => null,
            };
        } catch (\Throwable) {
            // Notification failure must never break the main flow
        }
    }
}
