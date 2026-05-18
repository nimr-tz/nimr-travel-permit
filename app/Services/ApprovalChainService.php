<?php

namespace App\Services;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Support\Collection;
use RuntimeException;

class ApprovalChainService
{
    /**
     * Build the ordered approval chain for a given traveller.
     *
     * Returns an array of steps, each being:
     *   ['stage' => string, 'approver_id' => int]
     *
     * Stages: supervisor | director | final | hr
     */
    public function buildChain(User $traveller): array
    {
        $unit = $traveller->unit;

        if (!$unit) {
            throw new RuntimeException("User [{$traveller->id}] has no unit assigned.");
        }

        return match (true) {
            $unit->type === 'research_centre' => $this->chainForCentre($traveller),
            $unit->type === 'hq_section'      => $this->chainForHqSection($traveller),
            $unit->type === 'hq_standalone'   => $this->chainForHqStandalone($traveller),
            $unit->type === 'hq_directorate'  => $this->chainForHqDirectorate($traveller),
            default => throw new RuntimeException("Unknown unit type [{$unit->type}]."),
        };
    }

    /**
     * After an approval action is recorded, advance the request to the
     * next step or mark it fully approved/rejected/returned.
     */
    public function advance(TravelRequest $request, string $decision): void
    {
        if ($decision === 'rejected') {
            $request->update([
                'status'              => TravelRequest::STATUS_REJECTED,
                'current_approver_id' => null,
            ]);
            return;
        }

        if ($decision === 'returned') {
            $chain        = $request->approval_chain;
            $currentIndex = collect($chain)->search(
                fn($step) => (int) $step['approver_id'] === (int) $request->current_approver_id
            );

            if ($currentIndex !== false && $currentIndex > 0) {
                // Non-first approver returns → route to the previous approver in the chain.
                // The requester is NOT involved; the previous approver re-reviews and decides
                // whether to re-approve (send it back up) or return it to the requester.
                $request->update([
                    'status'              => TravelRequest::STATUS_PENDING,
                    'current_approver_id' => $chain[$currentIndex - 1]['approver_id'],
                ]);
            } else {
                // First approver (or chain not found) returns → goes back to the requester.
                $request->update([
                    'status'              => TravelRequest::STATUS_RETURNED,
                    'current_approver_id' => null,
                    'submitted_at'        => null,
                ]);
            }
            return;
        }

        $chain     = $request->approval_chain;
        $currentId = $request->current_approver_id;

        $currentIndex = collect($chain)->search(fn($step) => (int)$step['approver_id'] === (int)$currentId);

        if ($currentIndex === false) {
            throw new \RuntimeException(
                "Approver [{$currentId}] not found in approval chain for request [{$request->id}]. Possible data corruption."
            );
        }

        $nextStep = $chain[$currentIndex + 1] ?? null;

        if ($nextStep) {
            $request->update([
                'current_approver_id' => $nextStep['approver_id'],
            ]);
        } else {
            $request->update([
                'status'              => TravelRequest::STATUS_APPROVED,
                'current_approver_id' => null,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Chain builders per unit type
    // ------------------------------------------------------------------

    private function chainForCentre(User $traveller): array
    {
        $unit          = $traveller->unit;
        $centreManager = $this->findInUnit($unit->id, 'centre_manager');
        $dg            = $this->findDirectorGeneral();

        return match ($traveller->role) {

            // Centre Manager travels → DG
            'centre_manager' => [
                ['stage' => 'final', 'approver_id' => $dg->id],
            ],

            // Staff with supervisor → supervisor → centre_manager
            // Staff without supervisor → centre_manager
            'staff', 'manager' => $traveller->supervisor_id
                ? [
                    ['stage' => 'supervisor', 'approver_id' => $traveller->supervisor_id],
                    ['stage' => 'final',      'approver_id' => $centreManager->id],
                ]
                : [
                    ['stage' => 'final', 'approver_id' => $centreManager->id],
                ],

            default => throw new RuntimeException("Unhandled role [{$traveller->role}] for research centre."),
        };
    }

    private function chainForHqSection(User $traveller): array
    {
        $unit   = $traveller->unit;
        $parent = $unit->parent; // the Directorate
        $dg     = $this->findDirectorGeneral();

        return match ($traveller->role) {

            // Head of Section → Director → DG
            'head' => [
                ['stage' => 'director', 'approver_id' => $this->findInUnit($parent->id, 'director')->id],
                ['stage' => 'final',    'approver_id' => $dg->id],
            ],

            // Staff → Head of Section → Director → DG
            'staff', 'manager' => [
                ['stage' => 'supervisor', 'approver_id' => $this->findInUnit($unit->id, 'head')->id],
                ['stage' => 'director',   'approver_id' => $this->findInUnit($parent->id, 'director')->id],
                ['stage' => 'final',      'approver_id' => $dg->id],
            ],

            default => throw new RuntimeException("Unhandled role [{$traveller->role}] for hq_section."),
        };
    }

    private function chainForHqStandalone(User $traveller): array
    {
        $unit = $traveller->unit;
        $dg   = $this->findDirectorGeneral();

        return match ($traveller->role) {

            // Manager → DG
            'manager' => [
                ['stage' => 'final', 'approver_id' => $dg->id],
            ],

            // Staff → unit manager → DG
            'staff' => [
                ['stage' => 'supervisor', 'approver_id' => $this->findInUnit($unit->id, 'manager')->id],
                ['stage' => 'final',      'approver_id' => $dg->id],
            ],

            default => throw new RuntimeException("Unhandled role [{$traveller->role}] for hq_standalone."),
        };
    }

    private function chainForHqDirectorate(User $traveller): array
    {
        $dg = $this->findDirectorGeneral();

        // Director → DG
        return [
            ['stage' => 'final', 'approver_id' => $dg->id],
        ];
    }

    // ------------------------------------------------------------------
    // Finders
    // ------------------------------------------------------------------

    private function findInUnit(int $unitId, string $role): User
    {
        $user = User::where('unit_id', $unitId)
                    ->where('role', $role)
                    ->where('is_active', true)
                    ->first();

        if (!$user) {
            throw new RuntimeException("No active user with role [{$role}] found in unit [{$unitId}].");
        }

        return $user;
    }

    private function findDirectorGeneral(): User
    {
        $dg = User::where('role', 'director_general')->where('is_active', true)->first();

        if (!$dg) {
            throw new RuntimeException("No active Director General found.");
        }

        return $dg;
    }

    private function findHqHr(): User
    {
        $hrUnit = \App\Models\Unit::where('code', 'HRMAS')->first();

        if (!$hrUnit) {
            throw new RuntimeException("HQ HR unit (HRMAS) not found.");
        }

        return $this->findInUnit($hrUnit->id, 'hr');
    }
}
