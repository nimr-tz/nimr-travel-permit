<x-app-layout>
<div class="px-6 py-6 lg:px-8 lg:py-8 space-y-5">

    {{-- Page header --}}
    <div class="rounded-xl border border-indigo-200 shadow-sm px-6 py-5 flex items-center justify-between gap-6" style="background:#eef2ff;">
        <div class="flex items-center gap-4">
            <div class="h-12 w-12 rounded-xl flex items-center justify-center shrink-0 border border-indigo-200" style="background:rgba(255,255,255,0.6);">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('users.title') }}</h1>
                <p class="text-sm font-medium text-indigo-700 mt-0.5">
                    @if ($users->total() > 0)
                        {{ $users->total() }} {{ Str::lower(__('users.stat_total_sub')) }}
                    @else
                        {{ __('users.subtitle') }}
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('users.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white shadow-sm transition hover:opacity-90 shrink-0"
           style="background-color:#05499c;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('users.new_user') }}
        </a>
    </div>

    {{-- Flash message --}}
    @if (session('status'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
        class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-700 text-sm">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="flex-1">{{ session('status') }}</span>
        <button @click="show = false" class="text-emerald-400 hover:text-emerald-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    @endif

    {{-- Stats row --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.stat_total') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ __('users.stat_total_sub') }}</p>
        </div>
        <div class="rounded-xl border border-emerald-100 px-5 py-4 shadow-sm" style="background:#f0fdf4;">
            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-700">{{ __('users.active') }}</p>
            <p class="mt-1 text-3xl font-bold text-emerald-800">{{ $stats['active'] }}</p>
            <p class="text-xs text-emerald-600 mt-0.5">{{ __('users.stat_active_sub') }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 shadow-sm">
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.inactive') }}</p>
            <p class="mt-1 text-3xl font-bold text-slate-500">{{ $stats['inactive'] }}</p>
            <p class="text-xs text-slate-400 mt-0.5">{{ __('users.stat_inactive_sub') }}</p>
        </div>
    </div>

    {{-- Search --}}
    <form method="GET" action="{{ route('users.index') }}" class="flex items-center gap-3">
        <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-xl shadow-sm px-4 py-2.5 w-full max-w-md focus-within:ring-2 focus-within:ring-indigo-100 focus-within:border-indigo-400 transition">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="{{ __('users.search_placeholder') }}"
                   class="flex-1 bg-transparent text-sm text-slate-800 placeholder-slate-400 outline-none min-w-0">
            @if (request('q'))
            <a href="{{ route('users.index') }}" class="text-slate-300 hover:text-slate-500 transition shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </a>
            @endif
        </div>
        <button type="submit"
                class="shrink-0 flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                style="background-color:#05499c;">
            {{ __('common.search') }}
        </button>
    </form>

    {{-- Table --}}
    @php
        $roleColors = [
            'staff'            => ['bg' => '#f1f5f9', 'text' => '#475569'],
            'head'             => ['bg' => '#eff6ff', 'text' => '#1d4ed8'],
            'manager'          => ['bg' => '#f5f3ff', 'text' => '#6d28d9'],
            'director'         => ['bg' => '#eef2ff', 'text' => '#4338ca'],
            'centre_manager'   => ['bg' => '#ecfeff', 'text' => '#0891b2'],
            'director_general' => ['bg' => '#fffbeb', 'text' => '#b45309'],
            'hr'               => ['bg' => '#f0fdf4', 'text' => '#15803d'],
            'system_admin'     => ['bg' => '#0f172a', 'text' => '#ffffff'],
        ];
    @endphp

    <div class="card overflow-hidden">
        <table class="w-full">
            <thead class="table-head">
                <tr>
                    <th class="table-th">{{ __('users.col_user') }}</th>
                    <th class="table-th hidden lg:table-cell">{{ __('users.col_unit') }}</th>
                    <th class="table-th">{{ __('users.col_role') }}</th>
                    <th class="table-th hidden sm:table-cell">{{ __('users.col_status') }}</th>
                    <th class="table-th w-24 text-right">{{ __('users.col_actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                @php
                    $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn($p) => substr($p, 0, 1))->join('');
                    $rc = $roleColors[$user->role] ?? $roleColors['staff'];
                @endphp
                <tr class="table-row group">
                    <td class="table-td">
                        <div class="flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="relative shrink-0">
                                <div class="h-9 w-9 rounded-xl flex items-center justify-center text-xs font-extrabold text-white overflow-hidden"
                                     style="background:linear-gradient(135deg,#03316e 0%,#05499c 55%,#0f8a4b 100%);">
                                    @if ($user->avatar_path)
                                        <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                    @else
                                        {{ strtoupper($initials ?: '?') }}
                                    @endif
                                </div>
                                <span class="absolute -right-0.5 -bottom-0.5 h-2.5 w-2.5 rounded-full border-2 border-white {{ $user->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                            </div>
                            {{-- Name + email + staff number --}}
                            <div class="min-w-0">
                                <div class="font-semibold text-slate-900 text-sm truncate">{{ $user->name }}</div>
                                <div class="text-xs text-slate-400 truncate">{{ $user->email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="table-td hidden lg:table-cell">
                        <div class="text-sm text-slate-700">{{ $user->unit?->name ?? '—' }}</div>
                        @if ($user->unit)
                        <div class="text-xs text-slate-400 mt-0.5">{{ __('common.unit_' . $user->unit->type) }}</div>
                        @endif
                    </td>
                    <td class="table-td">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold"
                              style="background:{{ $rc['bg'] }};color:{{ $rc['text'] }};">
                            {{ __('common.role_' . $user->role) }}
                        </span>
                    </td>
                    <td class="table-td hidden sm:table-cell">
                        @if ($user->is_active)
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>{{ __('users.active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>{{ __('users.inactive') }}
                            </span>
                        @endif
                    </td>
                    <td class="table-td text-right">
                        <a href="{{ route('users.edit', $user) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            {{ __('users.edit_btn') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="h-12 w-12 rounded-full bg-slate-100 flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </div>
                            @if (request('q'))
                                <p class="text-sm text-slate-500">{{ __('common.no_results_for', ['q' => request('q')]) }}</p>
                                <a href="{{ route('users.index') }}" class="text-sm text-indigo-600 hover:underline">{{ __('common.clear_search') }}</a>
                            @else
                                <p class="text-sm text-slate-400">{{ __('users.no_users') }}</p>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
    <div class="flex justify-center">
        {{ $users->links() }}
    </div>
    @endif

</div>
</x-app-layout>
