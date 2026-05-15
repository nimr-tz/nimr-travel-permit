<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">Ongeza Mtumiaji Mpya</h2>
            <a href="{{ route('users.index') }}" class="text-sm text-blue-700 hover:text-blue-900">← Rudi</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                @include('users._form', ['user' => null])
                <div class="mt-4 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-blue-700 text-white text-sm font-semibold rounded-md hover:bg-blue-800">Hifadhi</button>
                    <a href="{{ route('users.index') }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Ghairi</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
