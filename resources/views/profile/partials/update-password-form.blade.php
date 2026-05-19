<section>
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">{{ __('common.profile_security_title') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('common.profile_password_sub') }}</p>
        </div>
        <span class="hidden sm:inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">
            {{ __('common.profile_password_title') }}
        </span>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        @if ($errors->updatePassword->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->updatePassword->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="field md:col-span-2 max-w-md">
                <label class="label">{{ __('common.profile_current_pw') }}</label>
                <input id="update_password_current_password" type="password" name="current_password"
                    class="input @error('current_password', 'updatePassword') input-error @enderror"
                    autocomplete="current-password">
                @error('current_password', 'updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label class="label">{{ __('common.profile_new_pw') }}</label>
                <input id="update_password_password" type="password" name="password"
                    class="input @error('password', 'updatePassword') input-error @enderror"
                    autocomplete="new-password">
                @error('password', 'updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label class="label">{{ __('common.profile_confirm_pw') }}</label>
                <input id="update_password_password_confirmation" type="password" name="password_confirmation"
                    class="input @error('password_confirmation', 'updatePassword') input-error @enderror"
                    autocomplete="new-password">
                @error('password_confirmation', 'updatePassword')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                {{ __('common.profile_password_action') }}
            </button>
            @if (session('status') === 'password-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
               class="text-sm text-green-700 font-semibold">{{ __('common.profile_password_saved') }}</p>
            @endif
        </div>
    </form>
</section>
