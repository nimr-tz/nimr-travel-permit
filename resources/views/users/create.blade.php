<x-app-layout>
<div class="min-h-full p-5 lg:p-8" style="background:#f4f6fa;">

    <a href="{{ route('users.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 transition mb-5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ __('users.title') }}
    </a>

    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        <div class="grid grid-cols-1 xl:grid-cols-[320px,1fr] gap-6">

            {{-- ── Sidebar ─────────────────────────────────────── --}}
            <aside class="space-y-5">

                {{-- Info card --}}
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-24" style="background:linear-gradient(135deg,#03316e 0%,#05499c 60%,#1a6abf 100%);"></div>
                    <div class="px-5 pb-6">
                        <div class="-mt-9 mb-4">
                            <div class="h-16 w-16 rounded-xl border-4 border-white flex items-center justify-center shadow-md"
                                 style="background:linear-gradient(135deg,#03316e 0%,#05499c 55%,#0f8a4b 100%);">
                                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-lg font-bold text-slate-900">{{ __('users.create_info_title') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('users.create_info_body') }}</p>
                    </div>
                </section>

                {{-- Role guide --}}
                @php
                    $roleColors = [
                        'staff'            => ['#f1f5f9', '#475569'],
                        'head'             => ['#eff6ff', '#1d4ed8'],
                        'manager'          => ['#f5f3ff', '#6d28d9'],
                        'director'         => ['#eef2ff', '#4338ca'],
                        'centre_manager'   => ['#ecfeff', '#0891b2'],
                        'director_general' => ['#fffbeb', '#b45309'],
                        'hr'               => ['#f0fdf4', '#15803d'],
                        'system_admin'     => ['#0f172a', '#ffffff'],
                    ];
                @endphp
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">{{ __('users.role_guide') }}</p>
                    <div class="space-y-3">
                        @foreach ($roles as $role)
                        @php $rc = $roleColors[$role] ?? $roleColors['staff']; @endphp
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 shrink-0 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold"
                                  style="background:{{ $rc[0] }};color:{{ $rc[1] }};">
                                {{ __('common.role_' . $role) }}
                            </span>
                            <p class="text-xs text-slate-500 leading-relaxed">{{ __('users.role_desc_' . $role) }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

            </aside>

            {{-- ── Form sections + actions ─────────────────────── --}}
            <div class="space-y-5">
                @include('users._form', ['user' => null])

                <div class="flex items-center gap-3 pt-1">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        {{ __('users.save_user') }}
                    </button>
                    <a href="{{ route('users.index') }}" class="btn-ghost">{{ __('users.cancel') }}</a>
                </div>
            </div>

        </div>
    </form>

</div>
</x-app-layout>
