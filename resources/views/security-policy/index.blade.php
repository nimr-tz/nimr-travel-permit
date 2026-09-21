<x-app-layout>
@php
    $groups = [
        'password' => ['min_length', 'confirmation', 'hashing', 'reset_delivery'],
        'login'    => ['login_throttle', 'registration_throttle', 'reset_throttle'],
        'account'  => ['deactivation', 'auditing'],
    ];
@endphp

<div class="px-6 py-6 lg:px-8 lg:py-8 space-y-6">

    {{-- Page header --}}
    <div class="rounded-xl border border-indigo-200 shadow-sm px-6 py-5 flex items-center gap-4" style="background:#eef2ff;">
        <div class="h-12 w-12 rounded-xl flex items-center justify-center shrink-0 border border-indigo-200" style="background:rgba(255,255,255,0.6);">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('security_policy.title') }}</h1>
            <p class="text-sm font-medium text-indigo-700 mt-0.5">{{ __('security_policy.subtitle') }}</p>
        </div>
    </div>

    @foreach ($groups as $group => $items)
    <div class="space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-widest text-slate-500">{{ __('security_policy.group.'.$group) }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($items as $item)
            <div class="rounded-xl border border-slate-200 bg-white px-5 py-4 shadow-sm flex gap-3">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="text-sm font-semibold text-slate-900">{{ __('security_policy.item.'.$item.'.title') }}</p>
                    <p class="text-sm text-slate-500 mt-0.5">{{ __('security_policy.item.'.$item.'.desc') }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <p class="text-xs text-slate-400 border-t border-slate-200 pt-4">{{ __('security_policy.footer') }}</p>

</div>
</x-app-layout>
