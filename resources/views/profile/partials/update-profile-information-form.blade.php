<section>
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h3 class="text-base font-bold text-slate-900">{{ __('common.profile_contact_title') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('common.profile_info_sub') }}</p>
        </div>
        <span class="hidden sm:inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-bold text-blue-700">
            {{ __('common.profile_staff_record') }}
        </span>
    </div>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-5">
        @csrf
        @method('patch')

        @if ($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <label class="label">{{ __('common.profile_avatar_title') }}</label>
            <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="h-14 w-14 rounded-lg overflow-hidden border border-slate-200 bg-white flex items-center justify-center shrink-0">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::disk('public')->url($user->avatar_path) }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                    @else
                        <span class="text-sm font-bold text-white h-full w-full flex items-center justify-center" style="background:#0f8a4b;">
                            {{ strtoupper(Str::substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp"
                        class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
                    <p class="mt-2 text-xs text-slate-500">{{ __('common.profile_avatar_hint') }}</p>
                    @error('avatar')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

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
                <input type="email" @if (! $user->hasVerifiedEmail()) name="email" @endif value="{{ old('email', $user->email) }}"
                    class="input @error('email') input-error @enderror {{ $user->hasVerifiedEmail() ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : '' }}"
                    @if ($user->hasVerifiedEmail()) disabled @else required @endif autocomplete="username">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror

                @if ($user->hasVerifiedEmail())
                <p class="mt-2 text-xs text-slate-500">{{ __('common.profile_email_locked') }}</p>
                @elseif ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail)
                <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    {{ __('common.profile_unverified') }}
                    <button form="send-verification" class="ml-1 font-semibold underline hover:text-amber-950">
                        {{ __('common.profile_resend') }}
                    </button>
                    @if (session('status') === 'verification-link-sent')
                    <p class="mt-1 font-semibold text-green-700">{{ __('common.profile_link_sent') }}</p>
                    @endif
                </div>
                @endif
            </div>

            <div class="field">
                <label class="label">{{ __('dashboard.profile_phone') }}</label>
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="input @error('phone') input-error @enderror"
                    placeholder="+255 7XX XXX XXX" autocomplete="tel">
                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label class="label">{{ __('common.profile_job_title') }}</label>
                <input type="text" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                    class="input @error('job_title') input-error @enderror"
                    placeholder="{{ __('common.profile_job_title_placeholder') }}">
                @error('job_title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-2">
            <button type="submit" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('common.save_changes') }}
            </button>
            @if (session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
               class="text-sm text-green-700 font-semibold flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('common.profile_saved') }}
            </p>
            @endif
        </div>
    </form>
</section>
