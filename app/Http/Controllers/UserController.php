<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $admin = auth()->user();
        $q = $request->input('q', '');

        $query = $this->scopeUsersForAdmin(User::with('unit'), $admin);
        if ($q) {
            $query->where(fn ($s) => $s
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
            );
        }
        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        $allUsers = $this->scopeUsersForAdmin(User::select('is_active'), $admin)->get();
        $stats = [
            'total'    => $allUsers->count(),
            'active'   => $allUsers->where('is_active', true)->count(),
            'inactive' => $allUsers->where('is_active', false)->count(),
        ];

        return view('users.index', compact('users', 'stats'));
    }

    public function create(): View
    {
        $admin = auth()->user();
        $units = $this->unitsForAdmin($admin);
        $roles = $this->rolesForAdmin($admin);
        return view('users.create', compact('units', 'roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'unit_id'       => ['required', 'exists:units,id'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'job_title'     => ['nullable', 'string', 'max:255'],
            'role'          => ['required', 'in:' . implode(',', $this->rolesForAdmin($request->user()))],
            'is_active'     => ['boolean'],
        ]);

        $this->authorizeUnitForAdmin($request->user(), (int) $validated['unit_id']);

        $validated['password']         = Hash::make(Str::random(32));
        $validated['is_active']        = $request->boolean('is_active', true);
        $validated['email_verified_at'] = now();

        $user = User::create($validated);

        ActivityLog::record('created', $user);

        // Send a "set your password" invitation via the password reset mechanism
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('users.index')->with('status', __('users.invited_success', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $this->authorizeManagedUser(auth()->user(), $user);

        $admin = auth()->user();
        $units = $this->unitsForAdmin($admin);
        $roles = $this->rolesForAdmin($admin);
        return view('users.edit', compact('user', 'units', 'roles'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManagedUser($request->user(), $user);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email,' . $user->id],
            'password'      => ['nullable', 'string', 'min:8'],
            'unit_id'       => ['required', 'exists:units,id'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'job_title'     => ['nullable', 'string', 'max:255'],
            'role'          => ['required', 'in:' . implode(',', $this->rolesForAdmin($request->user()))],
            'is_active'     => ['boolean'],
        ]);

        $this->authorizeUnitForAdmin($request->user(), (int) $validated['unit_id']);

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

        $before = $user->only(['name', 'email', 'role', 'unit_id', 'is_active', 'job_title', 'phone']);
        $user->update($validated);
        $after  = $user->fresh()->only(['name', 'email', 'role', 'unit_id', 'is_active', 'job_title', 'phone']);

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
