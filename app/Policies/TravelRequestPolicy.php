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
}
