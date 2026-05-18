<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('auth.verify_title') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ __('auth.verify_sub') }}</p>
    </div>

    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-sm text-blue-800 leading-relaxed">
        {{ __('auth.verify_body') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ __('auth.verify_link_sent') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="justify-center py-2.5 px-5 text-sm rounded-xl text-white font-semibold flex items-center gap-2 shadow-sm transition hover:opacity-90 active:scale-[.98]" style="background-color:#05499c;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                {{ __('auth.verify_resend') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-slate-700 hover:underline transition">
                {{ __('auth.verify_logout') }}
            </button>
        </form>
    </div>

</x-guest-layout>
