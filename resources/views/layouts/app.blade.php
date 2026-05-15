<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'NIMR Travel Permit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50 text-gray-900">

<div x-data="{ sidebarOpen: false, collapsed: localStorage.getItem('sidebar') === 'collapsed' }"
     x-init="$watch('collapsed', v => localStorage.setItem('sidebar', v ? 'collapsed' : 'open'))"
     class="flex h-screen overflow-hidden">

    {{-- ================================================================ --}}
    {{-- SIDEBAR                                                           --}}
    {{-- ================================================================ --}}
    <aside
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            collapsed ? 'lg:w-16' : 'lg:w-64'
        ]"
        class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 flex flex-col transition-all duration-200 ease-in-out lg:static lg:inset-auto">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-4 h-14 border-b border-gray-100 shrink-0">
            <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-7 w-7 rounded-lg object-contain shrink-0">
            <div class="min-w-0 flex-1" :class="collapsed ? 'lg:hidden' : ''">
                <div class="text-sm font-bold text-gray-900 truncate">NIMR</div>
                <div class="text-xs text-gray-400 truncate">Ruhusa ya Kusafiri</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2 py-4 overflow-y-auto space-y-0.5">

            @php
                $user = auth()->user();
                $isActive = fn(string $route) => request()->routeIs($route)
                    ? 'bg-blue-50 text-blue-700 font-semibold'
                    : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900';

                $pendingApprovalsCount = 0;
                if ($user->isApprover()) {
                    $pendingApprovalsCount = \App\Models\TravelRequest::where('current_approver_id', $user->id)
                        ->where('status', 'pending')
                        ->where('requester_id', '!=', $user->id)
                        ->count();
                }
            @endphp

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}" title="Dashibodi"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('dashboard') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span :class="collapsed ? 'lg:hidden' : ''">Dashibodi</span>
            </a>

            @if ($user->isHr() || $user->isDirectorGeneral())
            {{-- HR / DG: single "all requests" link --}}
            <a href="{{ route('travel-requests.index') }}" title="Maombi Yote"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('travel-requests.*') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span :class="collapsed ? 'lg:hidden' : ''">Maombi Yote</span>
            </a>
            @else
            {{-- My Requests --}}
            <a href="{{ route('travel-requests.index') }}" title="Maombi Yangu"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('travel-requests.index') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span :class="collapsed ? 'lg:hidden' : ''">Maombi Yangu</span>
            </a>

            {{-- Approval Queue (approvers only) --}}
            @if ($user->isApprover())
            <a href="{{ route('approvals.index') }}" title="Idhini"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('approvals.*') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span :class="collapsed ? 'lg:hidden' : ''" class="flex-1">Idhini</span>
                @if ($pendingApprovalsCount > 0)
                <span :class="collapsed ? 'lg:hidden' : ''"
                    class="ml-auto px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">
                    {{ $pendingApprovalsCount }}
                </span>
                @endif
            </a>
            @endif

            {{-- New Request --}}
            <a href="{{ route('travel-requests.create') }}" title="Ombi Jipya"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('travel-requests.create') }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span :class="collapsed ? 'lg:hidden' : ''">Ombi Jipya</span>
            </a>
            @endif

            {{-- Admin section --}}
            @if ($user->isDirectorGeneral() || $user->isHr())
            <div class="pt-4">
                <p class="px-3 text-xs font-semibold text-gray-300 uppercase tracking-wider mb-1"
                    :class="collapsed ? 'lg:hidden' : ''">Usimamizi</p>
                <a href="{{ route('users.index') }}" title="Watumiaji"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition {{ $isActive('users.*') }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span :class="collapsed ? 'lg:hidden' : ''">Watumiaji</span>
                </a>
            </div>
            @endif

        </nav>
    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-cloak
        class="fixed inset-0 bg-black/30 z-40 lg:hidden"></div>

    {{-- ================================================================ --}}
    {{-- MAIN CONTENT                                                       --}}
    {{-- ================================================================ --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="h-14 bg-white border-b border-gray-200 flex items-center px-4 gap-4 shrink-0">

            {{-- Mobile hamburger --}}
            <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden p-1.5 rounded-md text-gray-500 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Desktop collapse toggle --}}
            <button @click="collapsed = !collapsed" class="hidden lg:flex p-1.5 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Page heading --}}
            <div class="flex-1 min-w-0">
                @isset($header)
                    {{ $header }}
                @endisset
            </div>

            {{-- User menu --}}
            <div x-data="{ open: false }" class="relative shrink-0">
                <button @click="open = !open"
                    class="flex items-center gap-2.5 px-3 py-1.5 rounded-md hover:bg-gray-100 transition">
                    <div class="h-7 w-7 rounded-full bg-blue-700 flex items-center justify-center text-xs font-bold text-white shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="hidden sm:block text-left">
                        <div class="text-sm font-medium text-gray-800 leading-none">{{ $user->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $user->unit?->name ?? '—' }}</div>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="open" @click.outside="open = false" x-cloak
                    class="absolute top-full right-0 mt-1 w-52 bg-white border border-gray-200 rounded-lg shadow-lg py-1 z-50">
                    <div class="px-4 py-2.5 border-b border-gray-100">
                        <div class="text-sm font-semibold text-gray-800">{{ $user->name }}</div>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $user->email }}</div>
                    </div>
                    <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Wasifu Wangu
                    </a>
                    <div class="border-t border-gray-100 my-1"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Toka
                        </button>
                    </form>
                </div>
            </div>

        </header>

        {{-- Scrollable page content --}}
        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>

    </div>
</div>

</body>
</html>
