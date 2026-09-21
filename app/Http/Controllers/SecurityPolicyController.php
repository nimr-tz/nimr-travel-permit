<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * A static, screenshot-friendly statement of the password/account security
 * rules the application enforces, for audit evidence. The figures here
 * mirror what's actually enforced in RegisteredUserController, NewPasswordController,
 * PasswordController and LoginRequest — there's no single runtime source to read
 * them from, so keep this page in sync by hand if those change.
 */
class SecurityPolicyController extends Controller
{
    public function __invoke(): View
    {
        return view('security-policy.index');
    }
}
