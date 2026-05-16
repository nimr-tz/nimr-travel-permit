<section>
    <div class="mb-5">
        <h3 class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">{{ __('common.profile_info_title') }}</h3>
        <p class="text-sm text-slate-500">{{ __('common.profile_info_sub') }}</p>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="field">
                <label class="label">{{ __('common.name') }} <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}"
                    class="input @error('name') input-error @enderror"
                    required autofocus autocomplete="name">
                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label class="label">{{ __('common.email') }} <span class="text-red-500">*</span></label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}"
                    class="input @error('email') input-error @enderror"
                    required autocomplete="username">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700">
                    {{ __('common.profile_unverified') }}
                    <button form="send-verification" class="underline ml-1 font-medium hover:text-amber-900">
                        {{ __('common.profile_resend') }}
                    </button>
                    @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 font-medium text-green-600">{{ __('common.profile_link_sent') }}</p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('common.save_changes') }}
            </button>
            @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
               class="text-sm text-green-600 font-medium flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('common.save_changes') }} ✓
            </p>
            @endif
        </div>
    </form>
</section>
