<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('Reset your password') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ __('Enter your email and we\'ll send you a reset link') }}</p>
    </div>

    @if (session('status'))
        <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label">{{ __('auth.email') }}</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    placeholder="{{ __('auth.email_placeholder') }}"
                    class="input pl-10 @error('email') input-error @enderror">
            </div>
            @error('email')
            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        <button type="submit" class="w-full justify-center py-3 text-base mt-2 rounded-xl text-white font-semibold flex items-center gap-2 shadow-sm transition hover:opacity-90 active:scale-[.98]" style="background-color:#05499c;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            {{ __('Send Reset Link') }}
        </button>
    </form>

    <p class="mt-5 text-center text-sm text-slate-500">
        {{ __('Remembered your password?') }}
        <a href="{{ route('login') }}" class="font-medium hover:underline" style="color:#05499c;">{{ __('auth.sign_in') }}</a>
    </p>

</x-guest-layout>
