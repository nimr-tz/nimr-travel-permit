<section>
    <div class="mb-5">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">{{ __('common.profile_password_title') }}</h3>
        <p class="text-sm text-slate-500">{{ __('common.profile_password_sub') }}</p>
    </div>

    <form method="post" action="{{ route('password.update') }}" class="space-y-5">
        @csrf
        @method('put')

        @if ($errors->updatePassword->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->updatePassword->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="field md:col-span-2 max-w-sm">
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

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('common.profile_password_title') }}
            </button>
            @if (session('status') === 'password-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
               class="text-sm text-green-600 font-medium">✓</p>
            @endif
        </div>
    </form>
</section>
