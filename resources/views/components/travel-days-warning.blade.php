@props([
    'travelDays' => null,
    'context' => 'requester', // requester | approver
])

@if ($travelDays)
    @php
        // Warn on the projected total when a specific trip is in view, otherwise
        // on what has already accumulated. The limit never blocks a submission.
        $showsProjection = $travelDays['projected'] !== $travelDays['accumulated'];
        $breached = $travelDays['projected_over_limit'] || $travelDays['over_limit'];
    @endphp

    @if ($breached)
        <div class="flex items-start gap-3 p-4 rounded-xl border border-amber-300 bg-amber-50">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-amber-800">
                    {{ __('travel.days_limit_title', ['limit' => $travelDays['limit']]) }}
                </p>
                <p class="text-xs text-amber-700 mt-1 leading-relaxed">
                    @if ($context === 'approver')
                        {{ __('travel.days_limit_body_approver', [
                            'days' => $travelDays['projected'],
                            'limit' => $travelDays['limit'],
                            'year' => $travelDays['financial_year_label'],
                        ]) }}
                    @elseif ($showsProjection)
                        {{ __('travel.days_limit_body_projected', [
                            'accumulated' => $travelDays['accumulated'],
                            'days' => $travelDays['projected'],
                            'limit' => $travelDays['limit'],
                            'year' => $travelDays['financial_year_label'],
                        ]) }}
                    @else
                        {{ __('travel.days_limit_body', [
                            'days' => $travelDays['accumulated'],
                            'limit' => $travelDays['limit'],
                            'year' => $travelDays['financial_year_label'],
                        ]) }}
                    @endif
                </p>
                <p class="text-[11px] text-amber-600 mt-1.5">{{ __('travel.days_limit_advisory') }}</p>
            </div>
        </div>
    @else
        <div class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50">
            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-xs text-slate-600">
                {{ __('travel.days_used', [
                    'days' => $showsProjection ? $travelDays['projected'] : $travelDays['accumulated'],
                    'limit' => $travelDays['limit'],
                    'year' => $travelDays['financial_year_label'],
                ]) }}
            </p>
        </div>
    @endif
@endif
