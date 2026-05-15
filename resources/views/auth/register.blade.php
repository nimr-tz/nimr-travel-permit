<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="staff_number" value="Staff Number" />
            <x-text-input id="staff_number" class="block mt-1 w-full" type="text" name="staff_number" :value="old('staff_number')" />
            <x-input-error :messages="$errors->get('staff_number')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="job_title" value="Job Title" />
            <x-text-input id="job_title" class="block mt-1 w-full" type="text" name="job_title" :value="old('job_title')" />
            <x-input-error :messages="$errors->get('job_title')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="unit_id" value="Primary Unit" />
            <select id="unit_id" name="unit_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select unit</option>
                @foreach ($units as $unit)
                    <option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('unit_id')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
