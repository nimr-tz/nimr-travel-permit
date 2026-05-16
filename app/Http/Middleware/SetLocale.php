<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', 'sw');

        if (!in_array($locale, ['en', 'sw'])) {
            $locale = 'sw';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
