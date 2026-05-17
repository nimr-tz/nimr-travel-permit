<?php

namespace App\Http\Controllers;

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
    public function index(): View
    {
        $users = User::with('unit', 'supervisor')
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $units = Unit::orderBy('type')->orderBy('name')->get();
        $roles = ['staff', 'head', 'manager', 'director', 'centre_manager', 'director_general', 'hr'];
        $supervisors = User::orderBy('name')->get();
        return view('users.create', compact('units', 'roles', 'supervisors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'unit_id'       => ['nullable', 'exists:units,id'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'staff_number'  => ['nullable', 'string', 'max:100'],
            'job_title'     => ['nullable', 'string', 'max:255'],
            'role'          => ['required', 'in:staff,head,manager,director,centre_manager,director_general,hr'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'is_active'     => ['boolean'],
        ]);

        $validated['password']         = Hash::make(Str::random(32));
        $validated['is_active']        = $request->boolean('is_active', true);
        $validated['email_verified_at'] = now();

        $user = User::create($validated);

        // Send a "set your password" invitation via the password reset mechanism
        Password::sendResetLink(['email' => $user->email]);

        return redirect()->route('users.index')->with('status', __('users.invited_success', ['name' => $user->name]));
    }

    public function edit(User $user): View
    {
        $units = Unit::orderBy('type')->orderBy('name')->get();
        $roles = ['staff', 'head', 'manager', 'director', 'centre_manager', 'director_general', 'hr'];
        $supervisors = User::where('id', '!=', $user->id)->orderBy('name')->get();
        return view('users.edit', compact('user', 'units', 'roles', 'supervisors'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email,' . $user->id],
            'password'      => ['nullable', 'string', 'min:8'],
            'unit_id'       => ['nullable', 'exists:units,id'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'staff_number'  => ['nullable', 'string', 'max:100'],
            'job_title'     => ['nullable', 'string', 'max:255'],
            'role'          => ['required', 'in:staff,head,manager,director,centre_manager,director_general,hr'],
            'supervisor_id' => ['nullable', 'exists:users,id'],
            'is_active'     => ['boolean'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        } else {
            $validated['password'] = Hash::make($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        return redirect()->route('users.index')->with('status', 'Mtumiaji amesasishwa.');
    }
}
