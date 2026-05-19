<x-app-layout>
<div class="p-4 sm:p-6 space-y-6">

    {{-- Page header --}}
    <div>
        <h2 class="text-xl font-bold text-slate-800">{{ __('nav.hr_reports') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('hr.reports_sub') }}</p>
    </div>

    {{-- Summary cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @php
            $cards = [
                ['label' => __('hr.stat_total'),    'value' => $stats['total'],    'color' => 'bg-slate-700'],
                ['label' => __('hr.stat_pending'),   'value' => $stats['pending'],  'color' => 'bg-amber-500'],
                ['label' => __('hr.stat_approved'),  'value' => $stats['approved'], 'color' => 'bg-emerald-600'],
                ['label' => __('hr.stat_rejected'),  'value' => $stats['rejected'], 'color' => 'bg-red-500'],
                ['label' => __('hr.stat_returned'),  'value' => $stats['returned'], 'color' => 'bg-orange-500'],
            ];
        @endphp
        @foreach ($cards as $card)
        <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-1">
            <span class="text-2xl font-bold text-slate-800">{{ $card['value'] }}</span>
            <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full {{ $card['color'] }}"></span>
                <span class="text-xs text-slate-500">{{ $card['label'] }}</span>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('hr.reports.index') }}"
          class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('common.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('hr.search_placeholder') }}"
                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('common.status') }}</label>
                <select name="status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— {{ __('common.all') }} —</option>
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" @selected(request('status') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('common.unit') }}</label>
                <select name="unit_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— {{ __('common.all') }} —</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((string)request('unit_id') === (string)$unit->id)>{{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('hr.date_from') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('hr.date_to') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>

        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit"
                    class="px-4 py-2 rounded-lg text-sm font-medium text-white shadow-sm"
                    style="background-color:#05499c;">
                {{ __('common.search') }}
            </button>
            <a href="{{ route('hr.reports.index') }}"
               class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                {{ __('common.reset') }}
            </a>
        </div>
    </form>

    {{-- Results table --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-sm text-slate-600">
                {{ __('common.showing', ['from' => $requests->firstItem() ?? 0, 'to' => $requests->lastItem() ?? 0, 'total' => $requests->total()]) }}
            </p>
            <a href="{{ route('hr.reports.export', request()->query()) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition hover:opacity-90 shrink-0"
               style="background-color:#0f8a4b;">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
        </div>

        @if ($requests->isEmpty())
        <div class="py-16 text-center text-slate-400">
            <svg class="mx-auto w-10 h-10 mb-3 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">{{ __('common.no_data') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('hr.col_applicant') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide hidden sm:table-cell">{{ __('common.unit') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">{{ __('common.destination') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide hidden md:table-cell">{{ __('hr.col_departure') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('common.status') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide hidden lg:table-cell">{{ __('hr.col_submitted') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($requests as $tr)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800">{{ $tr->b_applicant_name ?? $tr->requester?->name ?? '—' }}</div>
                            <div class="text-xs text-slate-400">{{ $tr->requester?->email }}</div>
                        </td>
                        <td class="px-4 py-3 hidden sm:table-cell text-slate-600">{{ $tr->unit?->name ?? '—' }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-slate-600 max-w-[180px] truncate">{{ $tr->b_destination ?? '—' }}</td>
                        <td class="px-4 py-3 hidden md:table-cell text-slate-600 whitespace-nowrap">
                            {{ $tr->b_departure_date?->format('d M Y') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $tr->statusColor() }}">
                                {{ $tr->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 hidden lg:table-cell text-slate-500 text-xs whitespace-nowrap">
                            {{ $tr->submitted_at?->format('d M Y, H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('travel-requests.show', $tr) }}"
                               class="text-xs font-medium text-blue-600 hover:text-blue-800">
                                {{ __('common.view') }} →
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($requests->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $requests->links() }}
        </div>
        @endif

        @endif
    </div>

</div>
</x-app-layout>
