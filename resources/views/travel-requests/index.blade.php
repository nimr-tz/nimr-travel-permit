<x-app-layout>
    <x-slot name="header">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">
            {{ ($user->isHr() || $user->isDirectorGeneral()) ? 'Maombi Yote' : 'Maombi Yangu' }}
        </h2>
    </x-slot>

    <div class="py-8 px-6 lg:px-12">

        @if (session('status'))
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl text-green-700 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @php
            $statusColors = [
                'draft'    => 'bg-gray-100 text-gray-500',
                'pending'  => 'bg-amber-100 text-amber-700',
                'approved' => 'bg-green-100 text-green-700',
                'rejected' => 'bg-red-100 text-red-700',
                'returned' => 'bg-orange-100 text-orange-700',
                'cancelled'=> 'bg-gray-100 text-gray-400',
            ];
            $statusLabels = [
                'draft'    => 'Rasimu',
                'pending'  => 'Inasubiri Idhini',
                'approved' => 'Imeidhinishwa',
                'rejected' => 'Imekataliwa',
                'returned' => 'Imerudishwa',
                'cancelled'=> 'Imefutwa',
            ];
        @endphp

        @forelse ($requests as $tr)
        <a href="{{ route('travel-requests.show', $tr) }}"
            class="flex items-start justify-between gap-6 py-6 border-b border-gray-100 hover:bg-gray-50 -mx-6 px-6 transition group last:border-0">
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-3 mb-2 flex-wrap">
                    <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="text-lg font-semibold text-gray-800 group-hover:text-blue-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tr->status] ?? 'bg-gray-100 text-gray-500' }}">
                        {{ $statusLabels[$tr->status] ?? $tr->status }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($user->isHr() || $user->isDirectorGeneral())
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $tr->b_applicant_name }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-sm">
                        {{ $tr->unit?->name ?? '—' }}
                    </span>
                    @endif
                    @if ($tr->b_departure_date)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        {{ $tr->b_departure_date->format('d M Y') }} — {{ $tr->b_return_date?->format('d M Y') ?? '?' }}
                    </span>
                    @endif
                    @if ($tr->status === 'pending' && $tr->currentApprover)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-100 text-orange-700 text-sm">
                        Kwa: {{ $tr->currentApprover->name }}
                    </span>
                    @elseif ($tr->status === 'draft')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-500 text-sm">
                        Haijasimilishwa
                    </span>
                    @endif
                </div>
            </div>
            <div class="text-right shrink-0">
                <div class="text-xs font-mono text-gray-400 mb-1">{{ $tr->request_number }}</div>
                @if ($tr->submitted_at)
                    <div class="text-xs text-gray-400">{{ $tr->submitted_at->format('d M Y') }}</div>
                @endif
            </div>
        </a>
        @empty
        <div class="py-24 text-center">
            <svg class="w-12 h-12 text-gray-200 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-lg text-gray-400 mb-2">Hakuna maombi yaliyopatikana.</p>
            @if (!$user->isHr() && !$user->isDirectorGeneral())
                <a href="{{ route('travel-requests.create') }}" class="text-blue-700 hover:underline text-sm">Wasilisha ombi la kwanza.</a>
            @endif
        </div>
        @endforelse

    </div>
</x-app-layout>
