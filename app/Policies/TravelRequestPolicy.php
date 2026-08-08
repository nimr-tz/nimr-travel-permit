<?php

namespace App\Policies;

use App\Models\TravelRequest;
use App\Models\User;

class TravelRequestPolicy
{
    public function view(User $user, TravelRequest $travelRequest): bool
    {
        if ($user->isDirectorGeneral()) {
            return true;
        }

        if ($user->isSystemAdmin()) {
            return $user->isGlobalSystemAdmin()
                || (int) $travelRequest->unit_id === (int) $user->unit_id;
        }

        if ($user->isHr()) {
            return $user->unit?->type === 'research_centre'
                ? $travelRequest->unit_id === $user->unit_id
                : true;
        }

        if ($travelRequest->requester_id === $user->id) {
            return true;
        }

        if ((int) $travelRequest->current_approver_id === $user->id) {
            return true;
        }

        if (collect($travelRequest->approval_chain)->contains(
            fn (array $step) => (int) ($step['approver_id'] ?? 0) === $user->id
        )) {
            return true;
        }

        return $travelRequest->approvalActions()->where('actor_id', $user->id)->exists();
    }

    public function update(User $user, TravelRequest $travelRequest): bool
    {
        return $travelRequest->requester_id === $user->id && $travelRequest->isEditable();
    }

    public function cancel(User $user, TravelRequest $travelRequest): bool
    {
        return $travelRequest->requester_id === $user->id && $travelRequest->isCancellable();
    }

    public function download(User $user, TravelRequest $travelRequest): bool
    {
        return $this->view($user, $travelRequest);
    }

    public function uploadReport(User $user, TravelRequest $travelRequest): bool
    {
        return $travelRequest->requester_id === $user->id
            && $travelRequest->status === TravelRequest::STATUS_APPROVED
            && ! $travelRequest->isTravelReportLocked();
    }

    public function downloadReport(User $user, TravelRequest $travelRequest): bool
    {
        return $this->view($user, $travelRequest);
    }

    public function unlockReport(User $user, TravelRequest $travelRequest): bool
    {
        if (! $user->isSystemAdmin() || ! $travelRequest->isTravelReportLocked()) {
            return false;
        }

        return $user->isGlobalSystemAdmin()
            || (int) $travelRequest->unit_id === (int) $user->unit_id;
    }
}
