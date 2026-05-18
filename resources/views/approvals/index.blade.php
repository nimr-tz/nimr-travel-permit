<x-app-layout>
    <x-slot name="header">
        <div class="page-header">
            <div>
                <h1 class="page-title">{{ __('approvals.title') }}</h1>
                <p class="page-sub">{{ __('approvals.subtitle') }}</p>
            </div>
            @if ($pending->count() > 0)
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 text-sm font-semibold">
                <span class="h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
                {{ __('approvals.requests_need', ['count' => $pending->count()]) }}
            </span>
            @endif
        </div>
    </x-slot>

    <div class="p-6 lg:p-8 space-y-8">

        {{-- ── Pending Action ───────────────────────────────────────────── --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-sm font-bold text-slate-800">{{ __('approvals.pending_action') }}</h3>
                @if ($pending->count() > 0)
                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500 text-white">{{ $pending->count() }}</span>
                @endif
            </div>

            @if ($pending->isEmpty())
            <div class="card p-14 text-center">
                <div class="h-12 w-12 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <p class="text-sm font-medium text-slate-500">{{ __('approvals.no_pending') }}</p>
                <p class="text-xs text-slate-400 mt-1">{{ __('approvals.no_pending_sub') }}</p>
            </div>
            @else
            <div class="card overflow-hidden divide-y divide-slate-100 border-t-4 border-amber-400">
                @foreach ($pending as $tr)
                <a href="{{ route('travel-requests.show', $tr) }}"
                    class="group flex items-start gap-5 px-5 py-5 hover:bg-amber-50/60 transition">
                    <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="text-sm font-bold text-slate-800 group-hover:text-amber-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                            <span class="badge-pending">{{ __('approvals.needs_approval') }}</span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $tr->b_applicant_name }}
                            </span>
                            @if ($tr->b_departure_date)
                            <span class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $tr->b_departure_date->format('d M Y') }} — {{ $tr->b_return_date?->format('d M Y') ?? '?' }}
                            </span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600">{{ $tr->unit?->name ?? '—' }}</span>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <div class="text-xs font-semibold text-amber-600 group-hover:text-amber-700">{{ __('approvals.view') }}</div>
                    </div>
                </a>
                @endforeach
            </div>
            @endif
        </div>

        {{-- ── History ──────────────────────────────────────────────────── --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h3 class="text-sm font-bold text-slate-800">{{ __('approvals.history') }}</h3>
                @if ($history->count() > 0)
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-200 text-slate-600">{{ $history->count() }}</span>
                @endif
            </div>

            @if ($history->isEmpty())
            <div class="card p-12 text-center">
                <p class="text-sm text-slate-400">{{ __('approvals.no_history') }}</p>
            </div>
            @else
            <div class="card overflow-hidden">
                <table class="w-full">
                    <thead class="table-head">
                        <tr>
                            <th class="table-th">{{ __('approvals.applicant') }}</th>
                            <th class="table-th">{{ __('approvals.destination') }}</th>
                            <th class="table-th hidden sm:table-cell">{{ __('approvals.date') }}</th>
                            <th class="table-th">{{ __('approvals.request_status') }}</th>
                            <th class="table-th">{{ __('approvals.my_decision') }}</th>
                            <th class="table-th w-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($history as $tr)
                        @php $myAction = $tr->approvalActions->first(); @endphp
                        <tr class="table-row cursor-pointer group" onclick="window.location='{{ route('travel-requests.show', $tr) }}'">
                            <td class="table-td font-medium text-slate-900 text-sm">{{ $tr->b_applicant_name }}</td>
                            <td class="table-td">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <span class="group-hover:text-indigo-700 transition">{{ $tr->b_destination ?? '—' }}</span>
                                </div>
                            </td>
                            <td class="table-td hidden sm:table-cell text-slate-500">
                                {{ $myAction?->acted_at->format('d M Y') ?? '—' }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $tr->statusColor() }}">{{ $tr->statusLabel() }}</span>
                            </td>
                            <td class="table-td">
                                @if ($myAction)
                                @php $dec = $myAction->decision; @endphp
                                <span class="badge {{ $dec === 'approved' ? 'badge-approved' : ($dec === 'returned' ? 'badge-returned' : 'badge-rejected') }}">
                                    {{ __('approvals.decided_' . $dec) }}
                                </span>
                                @endif
                            </td>
                            <td class="table-td">
                                <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-400 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

    </div>
</x-app-layout>
