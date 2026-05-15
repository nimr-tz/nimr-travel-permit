<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">Usimamizi wa Watumiaji</h2>
            <a href="{{ route('users.create') }}"
                class="px-4 py-2 bg-blue-700 text-white text-sm font-semibold rounded-md hover:bg-blue-800">
                + Mtumiaji Mpya
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="p-3 bg-green-50 border border-green-300 rounded-lg text-green-700 text-sm">{{ session('status') }}</div>
            @endif

            @php
                $roleLabels = [
                    'staff'           => 'Mtumishi',
                    'head'            => 'Mkuu wa Kitengo',
                    'manager'         => 'Meneja',
                    'director'        => 'Mkurugenzi',
                    'centre_manager'  => 'Meneja wa Kituo',
                    'director_general'=> 'Mkurugenzi Mkuu',
                    'hr'              => 'Rasilimali Watu',
                ];
            @endphp

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 bg-gray-50 text-xs text-gray-500 uppercase tracking-wide">
                            <th class="px-5 py-3 text-left">Jina</th>
                            <th class="px-5 py-3 text-left">Barua Pepe</th>
                            <th class="px-5 py-3 text-left">Kitengo</th>
                            <th class="px-5 py-3 text-left">Wadhifu</th>
                            <th class="px-5 py-3 text-left">Hali</th>
                            <th class="px-5 py-3 text-left">Msimamizi</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-medium text-gray-800">
                                {{ $user->name }}
                                @if ($user->staff_number)
                                    <div class="text-xs text-gray-400">{{ $user->staff_number }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $user->email }}</td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ $user->unit?->name ?? '—' }}
                                @if ($user->unit)
                                    <div class="text-xs text-gray-400">{{ $user->unit->type }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 rounded text-xs font-medium">
                                    {{ $roleLabels[$user->role] ?? $user->role }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? 'Hai' : 'Amezimwa' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-gray-500 text-xs">{{ $user->supervisor?->name ?? '—' }}</td>
                            <td class="px-3 py-3">
                                <a href="{{ route('users.edit', $user) }}" class="text-blue-700 hover:underline text-xs font-medium">Hariri</a>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">Hakuna watumiaji.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $users->links() }}

        </div>
    </div>
</x-app-layout>
