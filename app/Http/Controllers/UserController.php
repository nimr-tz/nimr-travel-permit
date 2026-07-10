<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use App\Services\SupervisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private SupervisorService $supervisors) {}

    public function index(Request $request): View
    {
        $admin = auth()->user();
        $q = $request->input('q', '');

        $query = $this->scopeUsersForAdmin(User::with(['unit', 'supervisor']), $admin);
        if ($q) {
            $query->where(fn ($s) => $s
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            );
        }
        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $allUsers = $this->scopeUsersForAdmin(User::select('is_active'), $admin)->get();
        $stats = [
            'total' => $allUsers->count(),
            'active' => $allUsers->where('is_active', true)->count(),
            'inactive' => $allUsers->where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    public function create(): View
    {
        $admin = auth()->user();
        $units = $this->unitsForAdmin($admin);
        $roles = $this->rolesForAdmin($admin);
        $supervisorOptions = $this->supervisorOptionsForAdmin($admin);

        return view('users.create', compact('units', 'roles', 'supervisorOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'unit_id' => ['required', 'exists:units,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:' . implode(',', $this->rolesForAdmin($request->user()))],
            'supervisor_id' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $this->authorizeUnitForAdmin($request->user(), (int) $validated['unit_id']);

        $validated['supervisor_id'] = $this->validatedSupervisorId(
            $request->user(),
            (int) $validated['unit_id'],
            $validated['role'],
            $validated['supervisor_id'] ?? null,
        );
        $validated['password'] = Hash::make(Str::random(32));
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['email_verified_at'] = now();

        $user = User::create($validated);

        ActivityLog::record('created', $user);

        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('users.index')->with('status', __('users.invited_success', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $this->authorizeManagedUser(auth()->user(), $user);

        $admin = auth()->user();
        $user->load(['unit', 'supervisor']);
        $units = $this->unitsForAdmin($admin);
        $roles = $this->rolesForAdmin($admin);
        $supervisorOptions = $this->supervisorOptionsForAdmin($admin, $user);

        return view('users.edit', compact('user', 'units', 'roles', 'supervisorOptions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagedUser($request->user(), $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8'],
            'unit_id' => ['required', 'exists:units,id'],
            'phone' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'role' => ['required', 'in:' . implode(',', $this->rolesForAdmin($request->user()))],
            'supervisor_id' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
        ]);

        $this->authorizeUnitForAdmin($request->user(), (int) $validated['unit_id']);

        $validated['supervisor_id'] = $this->validatedSupervisorId(
            $request->user(),
            (int) $validated['unit_id'],
            $validated['role'],
            $validated['supervisor_id'] ?? null,
            $user,
        );

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $newIsActive = $request->boolean('is_active');
        $validated['is_active'] = $newIsActive;

        if ($user->is_active && !$newIsActive) {
            $pendingCount = \App\Models\TravelRequest::where('current_approver_id', $user->id)
                ->where('status', 'pending')
                ->count();
            if ($pendingCount > 0) {
                return back()->withInput()->withErrors([
                    'is_active' => __('users.deactivate_pending_warning', ['count' => $pendingCount]),
                ]);
            }
        }

        $before = $user->only(['name', 'email', 'role', 'unit_id', 'supervisor_id', 'is_active', 'job_title', 'phone']);
        $user->update($validated);
        $after = $user->fresh()->only(['name', 'email', 'role', 'unit_id', 'supervisor_id', 'is_active', 'job_title', 'phone']);

        ActivityLog::record('updated', $user, ['before' => $before, 'after' => $after]);

        return redirect()->route('users.index')->with('status', 'Mtumiaji amesasishwa.');
    }

    private function rolesForAdmin(User $admin): array
    {
        if ($admin->isCentreSystemAdmin()) {
            return ['staff', 'manager', 'centre_manager', 'hr'];
        }

        return ['staff', 'head', 'manager', 'director', 'centre_manager', 'director_general', 'hr', 'system_admin'];
    }

    private function unitsForAdmin(User $admin)
    {
        if ($admin->isCentreSystemAdmin()) {
            return Unit::whereKey($admin->unit_id)->get();
        }

        return Unit::orderBy('type')->orderBy('name')->get();
    }

    private function supervisorOptionsForAdmin(User $admin, ?User $target = null)
    {
        $query = User::with('unit')
            ->where('is_active', true)
            ->whereIn('role', ['head', 'manager']);

        $this->scopeUsersForAdmin($query, $admin);

        if ($target) {
            $query->where('id', '!=', $target->id);
        }

        $directorGeneral = User::with('unit')
            ->where('role', 'director_general')
            ->where('is_active', true)
            ->when($target, fn ($dgQuery) => $dgQuery->where('id', '!=', $target->id))
            ->get();

        return $query->orderBy('name')->get()
            ->concat($directorGeneral)
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    private function validatedSupervisorId(
        User $admin,
        int $unitId,
        string $role,
        mixed $supervisorId,
        ?User $target = null,
    ): ?int {
        $fixedSupervisor = $this->supervisors->fixedSupervisorForAssignment($unitId, $role, $target?->id);
        $unit = Unit::find($unitId);

        if ($fixedSupervisor) {
            return $fixedSupervisor->id;
        }

        if ($unit && $this->supervisors->reportsDirectlyToDirectorGeneral($unit, $role)) {
            throw ValidationException::withMessages([
                'supervisor_id' => __('users.dg_supervisor_missing'),
            ]);
        }

        if (!$supervisorId) {
            return null;
        }

        $supervisorId = (int) $supervisorId;

        if (!$this->supervisors->isValidCandidate($supervisorId, $unitId, $role, $target?->id)) {
            throw ValidationException::withMessages([
                'supervisor_id' => __('dashboard.supervisor_invalid'),
            ]);
        }

        $supervisor = User::find($supervisorId);
        if (!$supervisor) {
            throw ValidationException::withMessages([
                'supervisor_id' => __('dashboard.supervisor_invalid'),
            ]);
        }

        $this->authorizeManagedUser($admin, $supervisor);

        return $supervisorId;
    }

    private function scopeUsersForAdmin($query, User $admin)
    {
        if ($admin->isCentreSystemAdmin()) {
            $query->where('unit_id', $admin->unit_id)
                ->where('role', '!=', 'system_admin');
        }

        return $query;
    }

    private function authorizeManagedUser(User $admin, User $target): void
    {
        if ($admin->isCentreSystemAdmin() && (int) $target->unit_id !== (int) $admin->unit_id) {
            abort(403);
        }

        if ($admin->isCentreSystemAdmin() && $target->isSystemAdmin()) {
            abort(403);
        }
    }

    private function authorizeUnitForAdmin(User $admin, int $unitId): void
    {
        if ($admin->isCentreSystemAdmin() && (int) $unitId !== (int) $admin->unit_id) {
            abort(403);
        }
    }
}
