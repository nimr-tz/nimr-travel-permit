<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('common.profile_title') }}</h1>
                <p class="page-sub">{{ __('common.profile_sub') }}</p>
            </div>
        </div>
    </x-slot>

    <div class="p-6 max-w-3xl mx-auto space-y-6">
        <div class="card p-6">
            @include('profile.partials.update-profile-information-form')
        </div>
        <div class="card p-6">
            @include('profile.partials.update-password-form')
        </div>
        <div class="card p-6 border border-red-100">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
