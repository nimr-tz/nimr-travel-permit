<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isSystemAdmin()) {
            abort(403, 'Huna ruhusa ya kufikia sehemu hii.');
        }

        return $next($request);
    }
}
