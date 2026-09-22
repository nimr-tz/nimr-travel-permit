<?php

namespace App\Services;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\User;
use App\Notifications\TravelRequestApprovedNotification;
use App\Notifications\TravelRequestHandoverNotification;
use App\Notifications\TravelRequestHrCopyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * A request left pending at the final approver (DG / centre manager) past its
 * own return date is stuck for a reason that has nothing to do with the
 * traveller: the trip already happened either way. Leaving it pending forever
 * blocks the traveller from submitting anything new (see
 * TravelRequestController::blockingOpenRequest()) and never enters the
 * report-reminder pipeline, which only chases *approved* trips.
 *
 * Whether a request is overdue is a plain fact of its own return date versus
 * today — nothing about it needs a clock tick to discover, so this is applied
 * lazily wherever it actually matters (viewing the request, trying to submit
 * a new one) rather than waiting on a scheduled sweep. The same rule mirrors
 * how SupervisorService::applyFixedSupervisor() is kept in sync on every load.
 *
 * Earlier stages (supervisor, director) are intentionally left alone — this
 * targets the specific DG/centre-manager bottleneck, not every stalled
 * approval.
 */
class OverdueApprovalResolver
{
    public function __construct(private ApprovalChainService $chainService, private AuditLogger $audit) {}

    /**
     * Resolve a single request if it qualifies. Safe to call on every page
     * load — it re-checks everything under a row lock and is a no-op unless
     * the request is still pending, still waiting at the final stage, and its
     * return date has actually passed.
     */
    public function resolve(TravelRequest $request): bool
    {
        if ($request->status !== TravelRequest::STATUS_PENDING
            || ! $request->b_return_date?->isBefore(today())
            || ! $this->isAtFinalStage($request)) {
            return false;
        }

        $resolved = DB::transaction(function () use ($request) {
            $locked = TravelRequest::lockForUpdate()->find($request->id);

            if (! $locked
                || $locked->status !== TravelRequest::STATUS_PENDING
                || ! $locked->b_return_date?->isBefore(today())
                || ! $this->isAtFinalStage($locked)) {
                return null;
            }

            $previousApproverId = $locked->current_approver_id;

            ApprovalAction::create([
                'travel_request_id' => $locked->id,
                'actor_id'           => null,
                'stage'              => 'final',
                'decision'           => 'approved',
                'comment'            => 'Auto-approved: return date passed without a decision from the final approver.',
                'acted_at'           => now(),
            ]);

            $locked->forceFill([
                'status'              => TravelRequest::STATUS_APPROVED,
                'current_approver_id' => null,
            ])->save();

            return [$locked, $this->audit->changesOf($locked), $previousApproverId];
        });

        if (! $resolved) {
            return false;
        }

        [$locked, $changes, $previousApproverId] = $resolved;

        $this->audit->log('travel_request.auto_approved', $locked, $changes, [
            'previous_approver_id' => $previousApproverId,
        ]);

        $this->notify($locked);

        $request->setRawAttributes($locked->getAttributes());

        return true;
    }

    /**
     * Resolve every one of a user's own requests that qualify. A user has at
     * most one live request at a time (see blockingOpenRequest()), so this is
     * cheap and scoped — used right before that block is evaluated, so a
     * traveller is never stuck behind a decision nobody is going to make.
     */
    public function resolveFor(User $user): void
    {
        TravelRequest::query()
            ->where('requester_id', $user->id)
            ->where('status', TravelRequest::STATUS_PENDING)
            ->whereDate('b_return_date', '<', today())
            ->get()
            ->each(fn (TravelRequest $request) => $this->resolve($request));
    }

    /**
     * Resolve every qualifying request institute-wide. Not required for
     * correctness — resolve()/resolveFor() already cover the moments that
     * matter — but useful as a manual sweep an admin can run on demand.
     */
    public function resolveAll(): int
    {
        return TravelRequest::query()
            ->whereNotNull('approval_chain')
            ->where('status', TravelRequest::STATUS_PENDING)
            ->whereDate('b_return_date', '<', today())
            ->get()
            ->filter(fn (TravelRequest $request) => $this->isAtFinalStage($request))
            ->filter(fn (TravelRequest $request) => $this->resolve($request))
            ->count();
    }

    private function isAtFinalStage(TravelRequest $request): bool
    {
        $step = collect($request->approval_chain)->firstWhere('approver_id', $request->current_approver_id);

        return ($step['stage'] ?? null) === 'final';
    }

    private function notify(TravelRequest $request): void
    {
        try {
            $request->requester?->notify(new TravelRequestApprovedNotification($request));

            $this->chainService->hrCopyRecipients($request)
                ->each(fn (User $hr) => $hr->notify(new TravelRequestHrCopyNotification($request, 'approved')));

            if ($request->g_handover_officer_id) {
                $officer = User::find($request->g_handover_officer_id);

                if ($officer && $officer->is_active) {
                    $officer->notify(new TravelRequestHandoverNotification(
                        $request,
                        TravelRequestHandoverNotification::STAGE_APPROVED,
                    ));
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to send auto-approval notification for request '.$request->request_number, [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
