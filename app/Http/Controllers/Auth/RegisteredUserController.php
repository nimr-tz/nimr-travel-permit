<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'units' => Unit::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $allowedDomain = config('app.allowed_email_domain', 'nimr.or.tz');

        $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'email'        => [
                'required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class,
                function (string $attribute, string $value, \Closure $fail) use ($allowedDomain) {
                    if (!str_ends_with(strtolower($value), '@' . $allowedDomain)) {
                        $fail(__('auth.email_domain_invalid', ['domain' => $allowedDomain]));
                    }
                },
            ],
            'staff_number' => ['nullable', 'string', 'max:100'],
            'job_title'    => ['nullable', 'string', 'max:255'],
            'unit_id'      => ['nullable', 'exists:units,id'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'staff_number' => $request->staff_number,
            'job_title'    => $request->job_title,
            'unit_id'      => $request->unit_id,
            'password'     => Hash::make($request->password),
            'is_active'    => true,
        ]);

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', __('auth.verify_email_sent'));
    }
}
