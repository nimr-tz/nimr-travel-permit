<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'NIMR Travel Permit') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

        {{-- Navigation --}}
        <header class="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800">
            <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-md bg-teal-700 flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="font-semibold text-teal-800 dark:text-teal-400 tracking-wide uppercase text-sm">
                        NIMR Travel Permit
                    </span>
                </div>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}"
                               class="px-4 py-1.5 bg-teal-700 hover:bg-teal-800 text-white rounded text-sm font-medium transition-colors">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="px-4 py-1.5 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white border border-gray-300 dark:border-gray-600 hover:border-gray-400 rounded text-sm transition-colors">
                                Log in
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}"
                                   class="px-4 py-1.5 bg-teal-700 hover:bg-teal-800 text-white rounded text-sm font-medium transition-colors">
                                    Register
                                </a>
                            @endif
                        @endguest
                    </nav>
                @endif
            </div>
        </header>

        {{-- Hero --}}
        <main class="flex-1 flex items-center justify-center px-6 py-16">
            <div class="max-w-2xl w-full text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-teal-700 mb-6">
                    <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <h1 class="text-3xl font-semibold text-gray-900 dark:text-gray-100 mb-3">
                    Internal Travel Permit System
                </h1>
                <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">
                    National Institute for Medical Research (NIMR)
                </p>
                <p class="text-gray-500 dark:text-gray-400 mb-10 max-w-lg mx-auto">
                    Submit, track, and approve official travel requests online — replacing the paper-based NIMR-ADM-F002 form with a structured digital workflow.
                </p>

                @auth
                    <a href="{{ url('/dashboard') }}"
                       class="inline-block px-8 py-3 bg-teal-700 hover:bg-teal-800 text-white rounded-lg font-medium text-sm transition-colors">
                        Go to Dashboard
                    </a>
                @else
                    <div class="flex items-center justify-center gap-4">
                        <a href="{{ route('login') }}"
                           class="px-8 py-3 bg-teal-700 hover:bg-teal-800 text-white rounded-lg font-medium text-sm transition-colors">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                               class="px-8 py-3 border border-gray-300 dark:border-gray-600 hover:border-teal-600 dark:hover:border-teal-500 text-gray-700 dark:text-gray-300 rounded-lg font-medium text-sm transition-colors">
                                Create account
                            </a>
                        @endif
                    </div>
                @endauth

                {{-- Feature highlights --}}
                <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 gap-6 text-left">
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-teal-700 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-1">Submit Requests</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Fill out the seven-section travel form and submit for approval in minutes.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-teal-700 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-1">Structured Approvals</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Each request follows the official NIMR approval chain, with HR copied for records only.</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-5">
                        <div class="w-8 h-8 rounded-lg bg-teal-50 dark:bg-teal-900/30 flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-teal-700 dark:text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <h3 class="font-medium text-gray-900 dark:text-gray-100 text-sm mb-1">Email Notifications</h3>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">Approvers and requesters are notified automatically at each stage of the process.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-t border-gray-200 dark:border-gray-800 py-5">
            <p class="text-center text-xs text-gray-400 dark:text-gray-600">
                &copy; {{ date('Y') }} National Institute for Medical Research, Tanzania. Internal use only.
            </p>
        </footer>
    </body>
</html>
