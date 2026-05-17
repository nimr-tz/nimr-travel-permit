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
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'hqStandaloneUnits' => Unit::query()
                ->where('type', 'hq_standalone')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'hqDirectorates' => Unit::query()
                ->with(['children' => fn ($query) => $query
                    ->where('type', 'hq_section')
                    ->where('is_active', true)
                    ->orderBy('name')])
                ->where('type', 'hq_directorate')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'researchCentres' => Unit::query()
                ->where('type', 'research_centre')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
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
            'organizational_level' => ['required', 'in:headquarters,research_centre'],
            'unit_id'      => ['required', 'exists:units,id'],
            'password'     => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $unit = $this->validateOrganizationalPlacement($request);

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'staff_number' => $request->staff_number,
            'job_title'    => $request->job_title,
            'unit_id'      => $unit->id,
            'password'     => Hash::make($request->password),
            'is_active'    => true,
        ]);

        event(new Registered($user));

        return redirect()->route('login')
            ->with('status', __('auth.verify_email_sent'));
    }

    private function validateOrganizationalPlacement(Request $request): Unit
    {
        $unit = Unit::query()
            ->whereKey($request->integer('unit_id'))
            ->where('is_active', true)
            ->first();

        if (!$unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'Please select an active NIMR unit.',
            ]);
        }

        if ($request->organizational_level === 'headquarters' && !$unit->isHq()) {
            throw ValidationException::withMessages([
                'unit_id' => 'Please select a headquarters unit.',
            ]);
        }

        if ($request->organizational_level === 'research_centre' && !$unit->isResearchCentre()) {
            throw ValidationException::withMessages([
                'unit_id' => 'Please select a research centre.',
            ]);
        }

        if ($unit->type === 'hq_section' && !$unit->parent()->where('type', 'hq_directorate')->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => 'The selected section is not attached to an active directorate.',
            ]);
        }

        return $unit;
    }
}
