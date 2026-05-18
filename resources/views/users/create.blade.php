<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('users.new_user') }}</h1>
                <p class="page-sub">{{ __('users.add_subtitle') }}</p>
            </div>
            <a href="{{ route('users.index') }}" class="btn-ghost btn-sm">{{ __('users.back') }}</a>
        </div>
    </x-slot>

    <div class="p-6 lg:p-8">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            @include('users._form', ['user' => null])
            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('users.save_user') }}
                </button>
                <a href="{{ route('users.index') }}" class="btn-ghost">{{ __('users.cancel') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
