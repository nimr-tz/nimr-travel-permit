<section x-data="{ open: false }" class="space-y-4">
    <div>
        <h3 class="text-sm font-bold text-red-700">{{ __('common.profile_delete_title') }}</h3>
        <p class="mt-1 text-sm text-slate-500">{{ __('common.profile_delete_sub') }}</p>
    </div>

    <button @click="open = true" type="button"
        class="inline-flex items-center gap-2 rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-50">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        {{ __('common.profile_delete_title') }}
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="background: rgba(15,23,42,0.55); backdrop-filter: blur(4px);">
        <div @click.outside="open = false"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl">

            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-100">
                    <svg class="w-5 h-5 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-900">{{ __('common.profile_delete_confirm') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('common.profile_delete_warning') }}</p>
                </div>
            </div>

            <form method="post" action="{{ route('profile.destroy') }}" class="mt-5 space-y-4">
                @csrf
                @method('delete')

                <div class="field">
                    <label class="label">{{ __('common.profile_current_pw') }}</label>
                    <input type="password" name="password"
                        class="input @error('password', 'userDeletion') input-error @enderror"
                        placeholder="Enter your password"
                        autocomplete="current-password">
                    @error('password', 'userDeletion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-1">
                    <button type="button" @click="open = false" class="btn-ghost">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn-danger">
                        {{ __('common.profile_delete_btn') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
