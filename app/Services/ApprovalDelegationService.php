<?php

namespace App\Services;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * An approver on approved travel is still the approver: their queue used to sit
 * untouched until they came back. Their handover officer stands in for them for
 * the days they are away.
 *
 * Nothing is rewritten to achieve this — current_approver_id stays the record of
 * whose decision this is, and the stand-in is resolved at read time. That keeps
 * the audit trail honest (ApprovalAction still records who actually clicked) and
 * means the delegation lapses on its own the day they return, with no scheduled
 * job to unwind it.
 *
 * Two rules are not negotiable:
 *  - Nobody approves their own request, delegate or not.
 *  - Authority never travels downward. The handover officer picker is scoped to
 *    the traveller's own unit, so a Centre Manager's officer is very often
 *    ordinary staff; handing them an approval queue would let a junior sign off
 *    their own colleagues' permits. Delegation therefore applies only when the
 *    officer already holds an approver role.
 */
class ApprovalDelegationService
{
    /**
     * Travel that is approved and covers the given day. Draft, pending,
     * rejected and cancelled requests never make anyone unavailable.
     */
    public function currentTravelFor(User $user, ?Carbon $on = null): ?TravelRequest
    {
        $day = ($on ?? Carbon::today())->toDateString();

        return TravelRequest::query()
            ->where('requester_id', $user->getKey())
            ->where('status', TravelRequest::STATUS_APPROVED)
            ->whereDate('b_departure_date', '<=', $day)
            ->whereDate('b_return_date', '>=', $day)
            ->orderBy('b_departure_date')
            ->first();
    }

    public function isAway(User $user, ?Carbon $on = null): bool
    {
        return $this->currentTravelFor($user, $on) !== null;
    }

    /**
     * The person standing in for this approver today, or null if they are here,
     * named nobody, or named somebody who may not hold approval authority.
     */
    public function delegateFor(User $approver, ?Carbon $on = null): ?User
    {
        $travel = $this->currentTravelFor($approver, $on);

        if (! $travel || ! $travel->g_handover_officer_id) {
            return null;
        }

        $officer = User::find($travel->g_handover_officer_id);

        if (! $officer || ! $officer->is_active || ! $officer->isApprover()) {
            return null;
        }

        return $officer;
    }

    /**
     * Ids of the approvers this user is currently standing in for. Drives the
     * pending queue, so a delegate sees the work without anything being moved.
     *
     * @return Collection<int, int>
     */
    public function actingFor(User $user, ?Carbon $on = null): Collection
    {
        if (! $user->isApprover()) {
            return collect();
        }

        $day = ($on ?? Carbon::today())->toDateString();

        return TravelRequest::query()
            ->where('status', TravelRequest::STATUS_APPROVED)
            ->where('g_handover_officer_id', $user->getKey())
            ->whereDate('b_departure_date', '<=', $day)
            ->whereDate('b_return_date', '>=', $day)
            ->pluck('requester_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    /**
     * May this user record a decision on this request — as the named approver,
     * or as the stand-in for one who is away?
     */
    public function mayActOn(TravelRequest $travelRequest, User $user): bool
    {
        if (! $travelRequest->current_approver_id) {
            return false;
        }

        // Never your own request, however you arrived at it.
        if ((int) $travelRequest->requester_id === (int) $user->getKey()) {
            return false;
        }

        if ((int) $travelRequest->current_approver_id === (int) $user->getKey()) {
            return true;
        }

        $approver = User::find($travelRequest->current_approver_id);

        if (! $approver) {
            return false;
        }

        return $this->delegateFor($approver)?->getKey() === $user->getKey();
    }
}
