<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($pageTitle){{ $pageTitle }} · @endisset{{ config('app.name', 'NIMR Travel Permit') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900">

<div x-data="{
        sideOpen: false,
        collapsed: localStorage.getItem('sidebar-collapsed') === '1',
        toggleCollapse() { this.collapsed = !this.collapsed; localStorage.setItem('sidebar-collapsed', this.collapsed ? '1' : '0'); }
     }"
     class="flex h-screen overflow-hidden">

    {{-- ================================================================== --}}
    {{-- SIDEBAR                                                             --}}
    {{-- ================================================================== --}}
    <aside
        :class="sideOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
        :style="collapsed ? 'width:64px' : 'width:240px'"
        class="fixed inset-y-0 left-0 z-50 flex flex-col bg-slate-900 transition-all duration-200 ease-in-out md:static md:inset-auto shrink-0"
        style="width:240px">

        {{-- Logo --}}
        <div class="flex items-center gap-4 h-20 px-5 border-b border-slate-800 shrink-0">
            <img src="{{ asset('NIMR.png') }}" alt="NIMR" class="h-11 w-11 object-contain shrink-0">
            <div x-show="!collapsed" class="min-w-0">
                <div class="text-sm font-semibold text-white leading-snug">{{ __('nav.system_name') }}</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-5 px-3 space-y-1.5">
            @php
                $user = auth()->user();

                $pendingCount = 0;
                if ($user->isApprover()) {
                    $pendingCount = \App\Models\TravelRequest::where('current_approver_id', $user->id)
                        ->where('status', 'pending')
                        ->where('requester_id', '!=', $user->id)
                        ->count();
                }

                $navItems = [];
                $navItems[] = ['route' => 'dashboard', 'label' => __('nav.dashboard'), 'icon' => 'home', 'badge' => null, 'pattern' => 'dashboard'];

                if ($user->isHr() || $user->isDirectorGeneral()) {
                    $navItems[] = ['route' => 'travel-requests.index', 'label' => __('nav.all_requests'), 'icon' => 'document-list', 'badge' => null, 'pattern' => 'travel-requests.*'];
                    if ($user->isDirectorGeneral()) {
                        $navItems[] = ['route' => 'approvals.index', 'label' => __('nav.approvals'), 'icon' => 'check-circle', 'badge' => $pendingCount ?: null, 'pattern' => 'approvals.*'];
                    }
                } else {
                    $navItems[] = ['route' => 'travel-requests.index', 'label' => __('nav.my_requests'), 'icon' => 'document-list', 'badge' => null, 'pattern' => 'travel-requests.index'];
                    $navItems[] = ['route' => 'travel-requests.create', 'label' => __('nav.new_request'), 'icon' => 'plus-circle', 'badge' => null, 'pattern' => 'travel-requests.create'];
                    if ($user->isApprover()) {
                        $navItems[] = ['route' => 'approvals.index', 'label' => __('nav.approvals'), 'icon' => 'check-circle', 'badge' => $pendingCount ?: null, 'pattern' => 'approvals.*'];
                    }
                }
            @endphp

            @foreach ($navItems as $item)
            @php $active = request()->routeIs($item['pattern']); @endphp
            <a href="{{ route($item['route']) }}"
               title="{{ $item['label'] }}"
               class="group flex items-center gap-3 px-3 py-3 rounded-lg text-sm transition-all
                      {{ $active ? 'text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
               style="{{ $active ? 'background-color:#05499c;' : '' }}"">
                <span class="shrink-0 w-5 h-5">
                    @if ($item['icon'] === 'home')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    @elseif ($item['icon'] === 'document-list')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    @elseif ($item['icon'] === 'plus-circle')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @elseif ($item['icon'] === 'check-circle')
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @endif
                </span>
                <span class="flex-1 truncate" x-show="!collapsed">{{ $item['label'] }}</span>
                @if (!empty($item['badge']))
                <span x-show="!collapsed"
                      class="ml-auto px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $active ? 'bg-white' : 'bg-amber-500 text-white' }}"
                      style="{{ $active ? 'color:#05499c;' : '' }}">
                    {{ $item['badge'] }}
                </span>
                @endif
            </a>
            @endforeach

            {{-- Admin section --}}
            @if ($user->isDirectorGeneral() || $user->isHr())
            <div class="pt-4 pb-1">
                <p x-show="!collapsed" class="px-3 text-[10px] font-semibold text-slate-600 uppercase tracking-widest mb-1">{{ __('nav.administration') }}</p>
            </div>
            @php $uActive = request()->routeIs('users.*'); @endphp
            <a href="{{ route('users.index') }}" title="{{ __('nav.users') }}"
               class="group flex items-center gap-3 px-3 py-3 rounded-lg text-sm transition-all
                      {{ $uActive ? 'text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}"
               style="{{ $uActive ? 'background-color:#05499c;' : '' }}">
                <span class="shrink-0 w-5 h-5">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
                <span x-show="!collapsed">{{ __('nav.users') }}</span>
            </a>
            @endif
        </nav>

    </aside>

    {{-- Mobile overlay --}}
    <div x-show="sideOpen" @click="sideOpen = false" x-cloak
         class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm md:hidden"></div>

    {{-- ================================================================== --}}
    {{-- MAIN CONTENT                                                        --}}
    {{-- ================================================================== --}}
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        {{-- Top bar --}}
        <header class="h-14 bg-white border-b border-slate-200 flex items-center px-4 gap-2 shrink-0 z-30">
            {{-- Mobile hamburger --}}
            <button @click="sideOpen = !sideOpen"
                    class="md:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            {{-- Desktop collapse --}}
            <button @click="toggleCollapse()"
                    class="hidden md:flex p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8M4 18h16"/></svg>
            </button>

            <div class="flex-1"></div>

            {{-- Right actions --}}
            <div class="flex items-center gap-3 shrink-0">

                {{-- Language toggle --}}
                <div class="flex items-center rounded-lg border border-slate-200 bg-white overflow-hidden text-xs font-semibold shadow-sm">
                    <a href="{{ route('locale.switch', 'sw') }}" title="Kiswahili"
                       class="flex items-center gap-1.5 px-3 py-1.5 transition {{ app()->getLocale() === 'sw' ? 'text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                       style="{{ app()->getLocale() === 'sw' ? 'background-color:#16a34a;' : '' }}">
                        <span class="fi fi-tz fis rounded-sm" style="font-size:14px;"></span>
                        <span>SW</span>
                    </a>
                    <div class="w-px h-5 bg-slate-200"></div>
                    <a href="{{ route('locale.switch', 'en') }}" title="English"
                       class="flex items-center gap-1.5 px-3 py-1.5 transition {{ app()->getLocale() === 'en' ? 'text-white' : 'text-slate-500 hover:bg-slate-50' }}"
                       style="{{ app()->getLocale() === 'en' ? 'background-color:#16a34a;' : '' }}">
                        <span class="fi fi-gb fis rounded-sm" style="font-size:14px;"></span>
                        <span>EN</span>
                    </a>
                </div>

                {{-- Notification bell (pending approvals) --}}
                @if (auth()->user()->isApprover() && isset($pendingCount) && $pendingCount > 0)
                <a href="{{ route('approvals.index') }}"
                   class="relative p-2 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
                </a>
                @endif

                {{-- User profile dropdown --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open"
                            class="h-9 w-9 rounded-full flex items-center justify-center text-xs font-bold text-white transition hover:opacity-90"
                            style="background-color:#05499c;">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </button>

                    <div x-show="open" @click.outside="open = false" x-cloak
                         class="absolute right-0 mt-1.5 w-52 rounded-xl bg-white border border-slate-200 shadow-lg overflow-hidden z-50">
                        <div class="px-4 py-3 border-b border-slate-100">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}"
                           class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ __('nav.profile') }}
                        </a>
                        <div class="border-t border-slate-100">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                    {{ __('nav.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto">
            {{ $slot }}
        </main>

        {{-- Global error toast --}}
        @if (session('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="fixed bottom-5 right-5 z-50 flex items-center gap-3 px-4 py-3.5 bg-red-600 text-white rounded-xl shadow-xl text-sm max-w-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="flex-1">{{ session('error') }}</span>
            <button @click="show = false" class="opacity-70 hover:opacity-100">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        @endif
    </div>
</div>
</body>
</html>
