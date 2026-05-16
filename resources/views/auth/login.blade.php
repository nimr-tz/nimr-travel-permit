<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">{{ __('auth.welcome_back') }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ __('auth.sign_in_desc') }}</p>
    </div>

    <!-- Session Status -->
    @if (session('status'))
    <div class="mb-5 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('status') }}
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div>
            <label for="email" class="label">{{ __('auth.email') }}</label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
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

        {{-- Password --}}
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="label mb-0">{{ __('auth.password') }}</label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs font-medium transition hover:underline" style="color:#05499c;">
                    {{ __('auth.forgot_password') }}
                </a>
                @endif
            </div>
            <div class="relative" x-data="{ show: false }">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <input id="password" :type="show ? 'text' : 'password'" name="password" required autocomplete="current-password"
                    placeholder="••••••••"
                    class="input pl-10 pr-10 @error('password') input-error @enderror">
                <button type="button" @click="show = !show"
                    :title="show ? '{{ __('auth.hide_password') }}' : '{{ __('auth.show_password') }}'"
                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-slate-600 transition">
                    <svg x-show="!show" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="show" x-cloak class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                </button>
            </div>
            @error('password')
            <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ $message }}
            </p>
            @enderror
        </div>

        {{-- Remember me --}}
        <div class="flex items-center gap-2.5">
            <input id="remember_me" type="checkbox" name="remember"
                class="h-4 w-4 rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 focus:ring-offset-0 cursor-pointer">
            <label for="remember_me" class="text-sm text-slate-600 cursor-pointer select-none">{{ __('auth.remember_me') }}</label>
        </div>

        {{-- Submit --}}
        <button type="submit" class="w-full justify-center py-3 text-base mt-2 rounded-xl text-white font-semibold flex items-center gap-2 shadow-sm transition hover:opacity-90 active:scale-[.98]" style="background-color:#05499c;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            {{ __('auth.sign_in') }}
        </button>
    </form>

    <div class="mt-6 flex items-start gap-2.5 p-3.5 rounded-xl border text-xs" style="background-color:#05499c0d; border-color:#05499c30; color:#05499c;">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <div>
            <p class="font-semibold">{{ __('auth.staff_only_title') }}</p>
            <p class="mt-0.5 opacity-75">{{ __('auth.staff_only_sub') }}</p>
        </div>
    </div>

</x-guest-layout>
