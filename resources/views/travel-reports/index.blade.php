<x-app-layout>
<div class="p-4 sm:p-6 space-y-6">
    <div>
        <h2 class="text-xl font-bold text-slate-800">{{ __('travel_reports.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ __('travel_reports.subtitle') }}</p>
    </div>

    <form method="GET" action="{{ route('travel-reports.index') }}" class="bg-white rounded-xl border border-slate-200 p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('travel_reports.financial_year') }}</label>
                <select name="financial_year" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($financialYears as $year)
                        <option value="{{ $year }}" @selected((int) $financialYear === (int) $year)>
                            {{ __('travel_reports.financial_year_label', ['start' => $year, 'end' => substr((string) ($year + 1), -2)]) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('travel_reports.centre') }}</label>
                <select name="centre" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— {{ __('travel_reports.all_centres') }} —</option>
                    @unless (auth()->user()->isCentreSystemAdmin())
                        <option value="hq" @selected(request('centre') === 'hq')>{{ __('travel_reports.headquarters') }}</option>
                    @endunless
                    @foreach ($centres as $centre)
                        <option value="{{ $centre->id }}" @selected((string) request('centre') === (string) $centre->id)>{{ $centre->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('travel_reports.station') }}</label>
                <select name="station_id" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— {{ __('travel_reports.all_stations') }} —</option>
                    @foreach ($stations as $station)
                        <option value="{{ $station->id }}" @selected((string) request('station_id') === (string) $station->id)>{{ $station->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('travel_reports.report_status') }}</label>
                <select name="report_status" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">— {{ __('travel_reports.all_statuses') }} —</option>
                    <option value="submitted" @selected(request('report_status') === 'submitted')>{{ __('travel_reports.submitted') }}</option>
                    <option value="missing" @selected(request('report_status') === 'missing')>{{ __('travel_reports.missing') }}</option>
                    <option value="not_due" @selected(request('report_status') === 'not_due')>{{ __('travel_reports.not_due') }}</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">{{ __('common.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('travel_reports.search_placeholder') }}"
                       class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="mt-3 flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium text-white shadow-sm" style="background-color:#05499c;">
                {{ __('common.search') }}
            </button>
            <a href="{{ route('travel-reports.index') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200">
                {{ __('common.reset') }}
            </a>
        </div>
    </form>

    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        @foreach ([
            ['label' => __('travel_reports.people'), 'value' => $stats['people'], 'color' => 'bg-blue-600'],
            ['label' => __('travel_reports.trips'), 'value' => $stats['trips'], 'color' => 'bg-slate-700'],
            ['label' => __('travel_reports.travel_days'), 'value' => $stats['days'], 'color' => 'bg-violet-600'],
            ['label' => __('travel_reports.over_limit', ['limit' => $annualLimit]), 'value' => $stats['over_limit'], 'color' => 'bg-amber-500'],
            ['label' => __('travel_reports.submitted_reports'), 'value' => $stats['submitted'], 'color' => 'bg-emerald-600'],
            ['label' => __('travel_reports.missing_reports'), 'value' => $stats['missing'], 'color' => 'bg-red-500'],
        ] as $card)
            <div class="bg-white rounded-xl border border-slate-200 p-4 flex flex-col gap-1">
                <span class="text-2xl font-bold text-slate-800">{{ $card['value'] }}</span>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full {{ $card['color'] }}"></span>
                    <span class="text-xs text-slate-500">{{ $card['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">{{ __('travel_reports.traveller_summary') }}</h3>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('travel_reports.traveller_summary_hint') }}</p>
        </div>
        @if ($people->isEmpty())
            <p class="p-8 text-center text-sm text-slate-400">{{ __('travel_reports.no_people') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.traveller') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('common.unit') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.trips') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.travel_days') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.submitted') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.missing') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($people as $person)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $person['name'] }}</div>
                                    <div class="text-xs text-slate-400">{{ $person['email'] }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ $person['unit'] }}</td>
                                <td class="px-4 py-3 text-right text-slate-700">{{ $person['trips'] }}</td>
                                <td class="px-4 py-3 text-right">
                                    <span class="font-bold {{ $person['over_limit'] ? 'text-amber-700' : 'text-violet-700' }}">{{ $person['days'] }}</span>
                                    @if ($person['over_limit'])
                                        <span class="ml-1.5 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 text-amber-800 border border-amber-300"
                                              title="{{ __('travel_reports.over_limit_hint', ['limit' => $annualLimit, 'days' => $person['days']]) }}">
                                            {{ __('travel_reports.over_limit_badge', ['limit' => $annualLimit]) }}
                                        </span>
                                    @elseif ($person['at_limit'])
                                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-slate-100 text-slate-600 border border-slate-300">
                                            {{ __('travel_reports.at_limit_badge') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-emerald-700">{{ $person['submitted'] }}</td>
                                <td class="px-4 py-3 text-right {{ $person['missing'] ? 'font-bold text-red-600' : 'text-slate-400' }}">{{ $person['missing'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between gap-3">
            <h3 class="text-sm font-bold text-slate-800">{{ __('travel_reports.trip_records') }}</h3>
            <span class="text-xs text-slate-500">{{ $requests->total() }}</span>
        </div>
        @if ($requests->isEmpty())
            <p class="p-8 text-center text-sm text-slate-400">{{ __('travel_reports.no_records') }}</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.traveller') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.destination') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.travel_dates') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.days') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.report') }}</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ __('travel_reports.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($requests as $tr)
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800">{{ $tr->requester?->name ?? $tr->b_applicant_name ?? '—' }}</div>
                                    <div class="text-xs text-slate-400">{{ $tr->unit?->name }} · {{ $tr->request_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600 max-w-[220px] truncate">{{ $tr->b_destination ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $tr->b_departure_date?->format('d M Y') }} – {{ $tr->b_return_date?->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-700">{{ $tr->financial_year_days ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($tr->isTravelReportLocked())
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ __('travel_reports.submitted') }}</span>
                                    @elseif ($tr->b_return_date?->isBefore(today()))
                                        <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">{{ __('travel_reports.missing') }}</span>
                                    @else
                                        <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600">{{ __('travel_reports.not_due') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap space-x-2">
                                    @if ($tr->travel_report_document)
                                        <a href="{{ route('travel-requests.report.download', $tr) }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">{{ __('travel_reports.download') }}</a>
                                    @endif
                                    <a href="{{ route('travel-requests.show', $tr) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">{{ __('travel_reports.view_request') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($requests->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">{{ $requests->links() }}</div>
            @endif
        @endif
    </div>
</div>
</x-app-layout>
