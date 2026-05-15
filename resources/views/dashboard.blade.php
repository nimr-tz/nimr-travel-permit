<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-800">Dashibodi</h2>
                <p class="mt-0.5 text-xs text-gray-500">Karibu, {{ $user->name }} — {{ $user->job_title ?? ucfirst($user->role) }} · {{ $user->unit?->name }}</p>
            </div>
            @if (!$user->isHr() && !$user->isDirectorGeneral())
            <a href="{{ route('travel-requests.create') }}"
                class="px-4 py-2 bg-blue-700 text-white text-sm font-semibold rounded-md hover:bg-blue-800">
                + Ombi Jipya
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-green-50 border border-green-300 rounded-lg text-green-700 text-sm">
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
                    'pending'  => 'Inasubiri',
                    'approved' => 'Imeidhinishwa',
                    'rejected' => 'Imekataliwa',
                    'returned' => 'Imerudishwa',
                    'cancelled'=> 'Imefutwa',
                ];
            @endphp

            {{-- Stats --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach ([
                    ['Maombi Yote',        $totalRequests, 'bg-white', 'text-gray-800'],
                    ['Yanasubiri Idhini',  $pendingCount,  'bg-yellow-50', 'text-yellow-800'],
                    ['Yaliyoidhinishwa',   $approvedCount, 'bg-green-50',  'text-green-800'],
                    ['Yaliyokataliwa',     $rejectedCount, 'bg-red-50',    'text-red-800'],
                ] as [$label, $count, $bg, $text])
                <div class="{{ $bg }} rounded-lg shadow-sm p-5 border border-gray-100">
                    <div class="text-xs font-medium text-gray-500">{{ $label }}</div>
                    <div class="mt-2 text-3xl font-bold {{ $text }}">{{ $count }}</div>
                </div>
                @endforeach
            </div>

            {{-- Needs My Action (approval queue) --}}
            @if (!$user->isHr() && !$user->isDirectorGeneral() && $needsMyAction->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Inahitaji Hatua Yako</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-500 text-white">{{ $needsMyAction->count() }}</span>
                </div>
                <div class="space-y-0 divide-y divide-gray-100 border border-amber-200 rounded-xl overflow-hidden bg-white">
                    @foreach ($needsMyAction as $tr)
                    <a href="{{ route('travel-requests.show', $tr) }}"
                        class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-amber-50 transition group">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-800 group-hover:text-amber-700">{{ $tr->b_destination ?? '—' }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">Inahitaji Idhini Yako</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $tr->b_applicant_name }}
                                @if ($tr->b_departure_date)
                                    · {{ $tr->b_departure_date->format('d M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono text-gray-400">{{ $tr->request_number }}</div>
                            <div class="text-xs font-semibold text-amber-600 mt-0.5">Angalia →</div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Approval queue (acted on but not mine) --}}
            @if (!$user->isHr() && !$user->isDirectorGeneral() && $approvalRequests->count() > 0)
            <div>
                <div class="flex items-center gap-3 mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Maombi ya Idhini</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-200 text-gray-600">{{ $approvalRequests->count() }}</span>
                </div>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
                    @foreach ($approvalRequests as $tr)
                    <a href="{{ route('travel-requests.show', $tr) }}"
                        class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 transition group">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-800 group-hover:text-blue-700">{{ $tr->b_destination ?? '—' }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tr->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $statusLabels[$tr->status] ?? $tr->status }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $tr->b_applicant_name }}
                                @if ($tr->b_departure_date)
                                    · {{ $tr->b_departure_date->format('d M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono text-gray-400">{{ $tr->request_number }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- My Requests --}}
            @if (!$user->isHr() && !$user->isDirectorGeneral())
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Maombi Yangu</h3>
                    @if ($myRequests->count() > 3)
                    <a href="{{ route('travel-requests.index') }}" class="text-xs text-blue-700 hover:underline">Ona yote →</a>
                    @endif
                </div>

                @if ($myRequests->isEmpty())
                <div class="py-10 text-center text-sm text-gray-400 bg-white rounded-xl border border-gray-100">
                    Bado hujawahi wasilisha ombi.
                    <a href="{{ route('travel-requests.create') }}" class="text-blue-700 hover:underline ml-1">Anza hapa.</a>
                </div>
                @else
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
                    @foreach ($myRequests->take(5) as $tr)
                    <a href="{{ route('travel-requests.show', $tr) }}"
                        class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 transition group">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-800 group-hover:text-blue-700">{{ $tr->b_destination ?? '—' }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tr->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $statusLabels[$tr->status] ?? $tr->status }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                @if ($tr->status === 'pending' && $tr->currentApprover)
                                    Kwa: {{ $tr->currentApprover->name }}
                                @elseif ($tr->b_departure_date)
                                    {{ $tr->b_departure_date->format('d M Y') }}
                                @endif
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono text-gray-400">{{ $tr->request_number }}</div>
                            @if ($tr->submitted_at)
                            <div class="text-xs text-gray-400 mt-0.5">{{ $tr->submitted_at->format('d M Y') }}</div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

            {{-- HR / DG: All Requests --}}
            @if ($user->isHr() || $user->isDirectorGeneral())
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-gray-800">Maombi Yote</h3>
                    <a href="{{ route('travel-requests.index') }}" class="text-xs text-blue-700 hover:underline">Ona yote →</a>
                </div>

                @if ($allRequests->isEmpty())
                <div class="py-10 text-center text-sm text-gray-400 bg-white rounded-xl border border-gray-100">
                    Hakuna maombi bado.
                </div>
                @else
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden divide-y divide-gray-100">
                    @foreach ($allRequests->take(10) as $tr)
                    <a href="{{ route('travel-requests.show', $tr) }}"
                        class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-gray-50 transition group">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-base font-semibold text-gray-800 group-hover:text-blue-700">{{ $tr->b_destination ?? '—' }}</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$tr->status] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $statusLabels[$tr->status] ?? $tr->status }}
                                </span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">
                                {{ $tr->b_applicant_name }}
                                @if ($tr->b_departure_date)
                                    · {{ $tr->b_departure_date->format('d M Y') }}
                                @endif
                                · {{ $tr->unit?->name }}
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-xs font-mono text-gray-400">{{ $tr->request_number }}</div>
                            @if ($tr->submitted_at)
                            <div class="text-xs text-gray-400 mt-0.5">{{ $tr->submitted_at->format('d M Y') }}</div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</x-app-layout>
