<x-app-layout>
    <x-slot name="header">
        <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">Idhini</h2>
    </x-slot>

    <div class="py-8 px-6 lg:px-12 space-y-10">

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
                'pending'  => 'Inasubiri',
                'approved' => 'Imeidhinishwa',
                'rejected' => 'Imekataliwa',
                'returned' => 'Imerudishwa',
                'cancelled'=> 'Imefutwa',
            ];
        @endphp

        {{-- Pending My Action --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-base font-bold text-gray-800">Inahitaji Hatua Yako</h3>
                @if ($pending->count() > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">{{ $pending->count() }}</span>
                @endif
            </div>

            @if ($pending->isEmpty())
            <div class="py-16 text-center bg-white rounded-2xl border border-gray-100">
                <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-400">Hakuna maombi yanayosubiri hatua yako.</p>
            </div>
            @else
            <div class="divide-y divide-gray-100 border border-amber-200 rounded-2xl overflow-hidden bg-white">
                @foreach ($pending as $tr)
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="flex items-start justify-between gap-6 py-5 px-6 hover:bg-amber-50 transition group">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-lg font-semibold text-gray-800 group-hover:text-amber-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">Inahitaji Idhini Yako</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $tr->b_applicant_name }}
                            </span>
                            @if ($tr->b_departure_date)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $tr->b_departure_date->format('d M Y') }} — {{ $tr->b_return_date?->format('d M Y') ?? '?' }}
                            </span>
                            @endif
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-600 text-sm">
                                {{ $tr->unit?->name ?? '—' }}
                            </span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs font-mono text-gray-400 mb-1">{{ $tr->request_number }}</div>
                        <div class="text-sm font-semibold text-amber-600">Angalia →</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- History --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-base font-bold text-gray-800">Nimeshughulika Nazo</h3>
                @if ($history->count() > 0)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">{{ $history->count() }}</span>
                @endif
            </div>

            @if ($history->isEmpty())
            <div class="py-16 text-center bg-white rounded-2xl border border-gray-100">
                <p class="text-gray-400">Bado haujashughulikia ombi lolote.</p>
            </div>
            @else
            <div class="divide-y divide-gray-100 border border-gray-200 rounded-2xl overflow-hidden bg-white">
                @foreach ($history as $tr)
                @php $myAction = $tr->approvalActions->first(); @endphp
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="flex items-start justify-between gap-6 py-5 px-6 hover:bg-gray-50 transition group">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="text-lg font-semibold text-gray-800 group-hover:text-blue-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tr->status] ?? 'bg-gray-100 text-gray-500' }}">
                                {{ $statusLabels[$tr->status] ?? $tr->status }}
                            </span>
                            @if ($myAction)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $myAction->decision === 'approved' ? 'bg-green-50 text-green-600' : ($myAction->decision === 'rejected' ? 'bg-red-50 text-red-600' : 'bg-orange-50 text-orange-600') }}">
                                Uliidhinisha: {{ ucfirst($myAction->decision) }}
                            </span>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-sm">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $tr->b_applicant_name }}
                            </span>
                            @if ($tr->b_departure_date)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-violet-100 text-violet-700 text-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $tr->b_departure_date->format('d M Y') }}
                            </span>
                            @endif
                            @if ($myAction)
                            <span class="text-xs text-gray-400">{{ $myAction->acted_at->format('d M Y, H:i') }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs font-mono text-gray-400">{{ $tr->request_number }}</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
