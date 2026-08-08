<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deactivation must take effect immediately, not at the next login.
 *
 * LoginRequest only checks is_active while authenticating, so without this a
 * deactivated user keeps a working session — and a "remember me" cookie keeps
 * letting them back in indefinitely.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = __('auth.deactivated');

            if ($request->expectsJson()) {
                abort(403, $message);
            }

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        return $next($request);
    }
}
