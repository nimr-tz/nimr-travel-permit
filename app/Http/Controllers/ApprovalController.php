<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestApprovedNotification;
use App\Notifications\TravelRequestHrCopyNotification;
use App\Notifications\TravelRequestRejectedNotification;
use App\Notifications\TravelRequestReturnedNotification;
use App\Notifications\TravelRequestSubmittedNotification;
use App\Services\ApprovalChainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    public function __construct(private ApprovalChainService $chainService) {}

    public function store(Request $request, TravelRequest $travelRequest): RedirectResponse
    {
        $user = $request->user();

        abort_unless((int) $travelRequest->current_approver_id === (int) $user->id, 403);
        abort_unless($travelRequest->status === TravelRequest::STATUS_PENDING, 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected,returned'],
            'comment'  => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['decision'] === 'returned') {
            $request->validate(['comment' => ['required', 'string', 'min:10', 'max:2000']]);
        }

        DB::transaction(function () use ($user, $travelRequest, $validated) {
            // Re-fetch with a row lock to prevent concurrent approvals on the same request
            $locked = TravelRequest::lockForUpdate()->findOrFail($travelRequest->id);

            abort_unless((int) $locked->current_approver_id === (int) $user->id, 403);
            abort_unless($locked->status === TravelRequest::STATUS_PENDING, 403);

            $chain = $locked->approval_chain;
            $step  = collect($chain)->firstWhere('approver_id', $user->id);
            $stage = $step['stage'] ?? 'supervisor';

            ApprovalAction::create([
                'travel_request_id' => $locked->id,
                'actor_id'          => $user->id,
                'stage'             => $stage,
                'decision'          => $validated['decision'],
                'comment'           => $validated['comment'] ?? null,
                'acted_at'          => now(),
            ]);

            $this->chainService->advance($locked, $validated['decision']);
        });

        $travelRequest->refresh();

        $this->sendNotifications($travelRequest, $validated['decision']);

        $messages = [
            'approved' => 'Umeidhinisha ombi hili.',
            'rejected' => 'Umekataa ombi hili.',
        ];

        if ($validated['decision'] === 'returned') {
            if ($travelRequest->status === TravelRequest::STATUS_PENDING && $travelRequest->current_approver_id) {
                $prev = User::find($travelRequest->current_approver_id);
                $statusMessage = 'Ombi limerudishwa kwa ' . ($prev?->name ?? 'msimamizi') . ' kwa mapitio.';
            } else {
                $statusMessage = 'Ombi limerudishwa kwa mwombaji kwa marekebisho.';
            }
        } else {
            $statusMessage = $messages[$validated['decision']];
        }

        return redirect()
            ->route('travel-requests.show', $travelRequest)
            ->with('status', $statusMessage);
    }

    private function sendNotifications(TravelRequest $travelRequest, string $decision): void
    {
        try {
            $requester = $travelRequest->requester;

            if ($decision === 'approved') {
                if ($travelRequest->status === TravelRequest::STATUS_APPROVED) {
                    // Fully approved — notify requester and send HR a copy.
                    $requester?->notify(new TravelRequestApprovedNotification($travelRequest));
                    $this->notifyHr($travelRequest, 'approved');
                } elseif ($travelRequest->status === TravelRequest::STATUS_PENDING && $travelRequest->current_approver_id) {
                    // Intermediate approval — notify next approver in the chain.
                    User::find($travelRequest->current_approver_id)
                        ?->notify(new TravelRequestSubmittedNotification($travelRequest));
                }
            } elseif ($decision === 'rejected') {
                $requester?->notify(new TravelRequestRejectedNotification($travelRequest));
                $this->notifyHr($travelRequest, 'rejected');
            } elseif ($decision === 'returned') {
                if ($travelRequest->status === TravelRequest::STATUS_PENDING && $travelRequest->current_approver_id) {
                    // Returned to the previous approver in the chain — notify them.
                    User::find($travelRequest->current_approver_id)
                        ?->notify(new TravelRequestSubmittedNotification($travelRequest));
                } else {
                    // Returned all the way to the requester.
                    $requester?->notify(new TravelRequestReturnedNotification($travelRequest));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send approval notification for request ' . $travelRequest->request_number, [
                'decision' => $decision,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    private function notifyHr(TravelRequest $travelRequest, string $event): void
    {
        try {
            $hrUnit = \App\Models\Unit::where('code', 'HRMAS')->first();
            if ($hrUnit) {
                User::where('unit_id', $hrUnit->id)->where('role', 'hr')->where('is_active', true)
                    ->each(fn($hr) => $hr->notify(new TravelRequestHrCopyNotification($travelRequest, $event)));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send HR copy notification for request ' . $travelRequest->request_number, [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
