<x-app-layout>
<div class="min-h-full p-5 lg:p-8" style="background:#f4f6fa;">

    <a href="{{ route('users.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('users.title') }}
    </a>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PATCH')
        <div class="grid grid-cols-1 xl:grid-cols-[320px,1fr] gap-6">

            {{-- ── Sidebar ─────────────────────────────────────── --}}
            @php
                $initials = collect(explode(' ', trim($user->name)))->filter()->take(2)->map(fn($p) => substr($p, 0, 1))->join('');
                $sideRoleColors = [
                    'staff'            => ['#f1f5f9', '#475569'],
                    'head'             => ['#eff6ff', '#1d4ed8'],
                    'manager'          => ['#f5f3ff', '#6d28d9'],
                    'director'         => ['#eef2ff', '#4338ca'],
                    'centre_manager'   => ['#ecfeff', '#0891b2'],
                    'director_general' => ['#fffbeb', '#b45309'],
                    'hr'               => ['#f0fdf4', '#15803d'],
                    'system_admin'     => ['#0f172a', '#ffffff'],
                ];
                $rc = $sideRoleColors[$user->role] ?? $sideRoleColors['staff'];
            @endphp
            <aside class="space-y-5">

                {{-- User profile card --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-24" style="background:linear-gradient(135deg,#03316e 0%,#05499c 60%,#1a6abf 100%);"></div>
                    <div class="px-5 pb-6">
                        <div class="-mt-10">
                            @if ($user->avatar_path)
                                <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}"
                                     class="h-20 w-20 rounded-xl border-4 border-white object-cover shadow-md">
                            @else
                                <div class="h-20 w-20 rounded-xl border-4 border-white text-white text-2xl font-bold flex items-center justify-center shadow-md"
                                     style="background:linear-gradient(135deg,#03316e 0%,#05499c 55%,#0f8a4b 100%);">
                                    {{ strtoupper($initials ?: '?') }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 min-w-0">
                            <p class="text-xl font-bold text-slate-900 truncate">{{ $user->name }}</p>
                            <p class="mt-1 text-sm text-slate-500 truncate">{{ $user->email }}</p>
                        </div>

                        <div class="mt-5 space-y-3">
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('common.profile_role') }}</p>
                                <span class="mt-2 inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold"
                                      style="background:{{ $rc[0] }};color:{{ $rc[1] }};">
                                    {{ __('common.role_' . $user->role) }}
                                </span>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('common.unit') }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $user->unit?->name ?? '—' }}</p>
                                @if ($user->unit)
                                <p class="text-xs text-slate-400 mt-0.5">{{ __('common.unit_' . $user->unit->type) }}</p>
                                @endif
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('users.col_status') }}</p>
                                <p class="mt-1 text-sm font-semibold {{ $user->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                    @if ($user->is_active)
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                            {{ __('users.active') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                                            {{ __('users.inactive') }}
                                        </span>
                                    @endif
                                </p>
                            </div>
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Member since</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $user->created_at?->format('d M Y') ?? '—' }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Note card --}}
                <section class="rounded-xl border border-amber-200 p-4 shadow-sm" style="background:#fffbeb;">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-amber-700">{{ __('users.edit_note_title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-amber-800">{{ __('users.edit_note_body') }}</p>
                </section>

            </aside>

            {{-- ── Form sections + actions ─────────────────────── --}}
            <div class="space-y-5">
                @include('users._form')

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('users.save_changes') }}
                    </button>
                    <a href="{{ route('users.index') }}" class="btn-ghost">{{ __('users.cancel') }}</a>
                </div>
            </div>

        </div>
    </form>

</div>
</x-app-layout>
