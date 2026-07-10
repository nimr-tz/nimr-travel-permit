<?php

namespace App\Services;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SupervisorService
{
    public function candidatesFor(User $user): Collection
    {
        $user->loadMissing('unit');

        return $this->candidatesForAssignment($user->unit, $user->role, $user->id);
    }

    public function fixedSupervisorFor(User $user): ?User
    {
        $user->loadMissing('unit');

        return $this->fixedSupervisorForAssignment($user->unit, $user->role, $user->id);
    }

    public function applyFixedSupervisor(User $user): ?User
    {
        $fixedSupervisor = $this->fixedSupervisorFor($user);

        if ($fixedSupervisor && (int) $user->supervisor_id !== (int) $fixedSupervisor->id) {
            $user->forceFill(['supervisor_id' => $fixedSupervisor->id])->save();
        }

        return $fixedSupervisor;
    }

    public function candidatesForAssignment(Unit|int|null $unit, string $role, ?int $excludeUserId = null): Collection
    {
        $unit = $this->resolveUnit($unit);

        if (!$unit) {
            return new Collection();
        }

        $fixedSupervisor = $this->fixedSupervisorForAssignment($unit, $role, $excludeUserId);

        if ($fixedSupervisor) {
            return new Collection([$fixedSupervisor]);
        }

        $supervisorRole = $this->supervisorRoleFor($unit, $role);

        if (!$supervisorRole) {
            return new Collection();
        }

        return User::query()
            ->where('unit_id', $unit->id)
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->where('is_active', true)
            ->where('role', $supervisorRole)
            ->orderBy('name')
            ->get();
    }

    public function isValidCandidate(int $supervisorId, Unit|int|null $unit, string $role, ?int $excludeUserId = null): bool
    {
        return $this->candidatesForAssignment($unit, $role, $excludeUserId)
            ->contains('id', $supervisorId);
    }

    public function isRequiredFor(User $user): bool
    {
        $user->loadMissing('unit');

        return $this->isRequiredForAssignment($user->unit, $user->role);
    }

    public function isRequiredForAssignment(Unit|int|null $unit, string $role): bool
    {
        $unit = $this->resolveUnit($unit);

        return $unit && $this->supervisorRoleFor($unit, $role) !== null;
    }

    public function fixedSupervisorForAssignment(Unit|int|null $unit, string $role, ?int $excludeUserId = null): ?User
    {
        $unit = $this->resolveUnit($unit);

        if (!$unit || !$this->reportsDirectlyToDirectorGeneral($unit, $role)) {
            return null;
        }

        return User::query()
            ->where('role', 'director_general')
            ->where('is_active', true)
            ->when($excludeUserId, fn ($query) => $query->where('id', '!=', $excludeUserId))
            ->orderBy('name')
            ->first();
    }

    public function reportsDirectlyToDirectorGeneral(Unit $unit, string $role): bool
    {
        return match ($unit->type) {
            'research_centre' => $role === 'centre_manager',
            'hq_standalone' => $role === 'manager',
            'hq_directorate' => $role === 'director',
            default => false,
        };
    }

    public function supervisorRoleFor(Unit $unit, string $role): ?string
    {
        if ($unit->type === 'research_centre' && in_array($role, ['staff', 'manager', 'hr', 'system_admin'], true)) {
            return 'manager';
        }

        if ($unit->type === 'hq_section' && in_array($role, ['staff', 'manager', 'hr', 'system_admin'], true)) {
            return 'head';
        }

        if ($unit->type === 'hq_standalone' && in_array($role, ['staff', 'hr', 'system_admin'], true)) {
            return 'manager';
        }

        return null;
    }

    private function resolveUnit(Unit|int|null $unit): ?Unit
    {
        if ($unit instanceof Unit) {
            return $unit;
        }

        if (!$unit) {
            return null;
        }

        return Unit::find($unit);
    }
}
