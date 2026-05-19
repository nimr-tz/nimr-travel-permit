<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('common.profile_title') }}</h1>
                <p class="page-sub">{{ __('common.profile_sub') }}</p>
            </div>
        </div>
    </x-slot>

    @php
        $initials = collect(explode(' ', trim($user->name)))
            ->filter()
            ->take(2)
            ->map(fn($part) => Str::substr($part, 0, 1))
            ->join('');
        $profileRows = [
            __('common.unit') => $user->unit?->name ?? __('dashboard.profile_not_set'),
            __('common.profile_role') => __('common.role_' . $user->role),
            __('common.profile_supervisor') => $user->supervisor?->name ?? __('dashboard.profile_not_set'),
        ];
    @endphp

    <div class="min-h-full p-5 lg:p-8" style="background:#f4f6fa;">
        <div class="grid grid-cols-1 xl:grid-cols-[360px,1fr] gap-6">
            <aside class="space-y-5">
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="h-28" style="background:linear-gradient(135deg,#03316e 0%,#05499c 60%,#1a6abf 100%);"></div>
                    <div class="px-6 pb-6">
                        <div class="-mt-10">
                            @if ($user->avatar_path)
                                <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}"
                                     class="h-20 w-20 rounded-xl border-4 border-white object-cover shadow-md">
                            @else
                                <div class="h-20 w-20 rounded-xl border-4 border-white text-white text-2xl font-bold flex items-center justify-center shadow-md"
                                     style="background:#0f8a4b;">
                                    {{ strtoupper($initials ?: 'U') }}
                                </div>
                            @endif
                        </div>

                        <div class="mt-4 min-w-0">
                            <p class="text-xl font-bold text-slate-900 truncate">{{ $user->name }}</p>
                            <p class="mt-1 text-sm text-slate-500 truncate">{{ $user->job_title ?: __('common.role_' . $user->role) }}</p>
                        </div>

                        <div class="mt-6 space-y-3">
                            @foreach ($profileRows as $label => $value)
                            <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ $label }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-800">{{ $value }}</p>
                            </div>
                            @endforeach
                        </div>

                        <div class="mt-5 rounded-lg px-4 py-3"
                             style="background:{{ $user->hasVerifiedEmail() ? '#ecfdf5' : '#fff7ed' }};border:1px solid {{ $user->hasVerifiedEmail() ? '#bbf7d0' : '#fed7aa' }};">
                            <p class="text-[10px] font-bold uppercase tracking-widest"
                               style="color:{{ $user->hasVerifiedEmail() ? '#15803d' : '#c2410c' }};">
                                {{ __('common.status') }}
                            </p>
                            <p class="mt-1 text-sm font-semibold"
                               style="color:{{ $user->hasVerifiedEmail() ? '#14532d' : '#9a3412' }};">
                                {{ $user->hasVerifiedEmail() ? __('common.profile_verified') : __('common.profile_unverified_short') }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ __('common.profile_account_title') }}</p>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ __('common.profile_account_sub') }}</p>
                </section>
            </aside>

            <div class="space-y-5">
                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    @include('profile.partials.update-profile-information-form')
                </section>

                <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    @include('profile.partials.update-password-form')
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
