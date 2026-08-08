<?php

namespace App\Services;

use App\Models\TravelRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Accumulated days out of office, per person, per financial year.
 *
 * NIMR runs a July–June financial year. A person should not be out of the
 * office for more than ANNUAL_LIMIT days within one such year. The limit is
 * advisory — it is surfaced as a warning and never blocks a submission.
 *
 * Days are taken from the dates on the form (b_departure_date → b_return_date)
 * inclusive of both. Allowance and cost fields never contribute.
 */
class TravelDaysService
{
    /** Advisory ceiling on days out of office per financial year. */
    public const ANNUAL_LIMIT = 60;

    /** The financial year a date falls in, identified by its starting year. */
    public function financialYearFor(?Carbon $date = null): int
    {
        $date ??= now();

        return $date->month >= 7 ? $date->year : $date->year - 1;
    }

    /**
     * Start and end of a financial year, identified by its starting year.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function window(int $financialYear): array
    {
        return [
            Carbon::create($financialYear, 7, 1)->startOfDay(),
            Carbon::create($financialYear + 1, 6, 30)->endOfDay(),
        ];
    }

    /**
     * Days of a single trip that fall inside the given window, inclusive of
     * both the departure and return dates.
     *
     * A trip straddling 30 June is split, so each day counts against the year
     * it was actually spent in. Trips that have not yet ended count zero.
     */
    public function daysInWindow(TravelRequest $travelRequest, Carbon $windowStart, Carbon $windowEnd): int
    {
        if (! $travelRequest->b_departure_date || ! $travelRequest->b_return_date) {
            return 0;
        }

        $departure = $travelRequest->b_departure_date->copy()->startOfDay();
        $return = $travelRequest->b_return_date->copy()->startOfDay();

        if ($return->isFuture()) {
            return 0;
        }

        $from = $departure->greaterThan($windowStart) ? $departure : $windowStart->copy();
        $to = $return->lessThan($windowEnd) ? $return : $windowEnd->copy();

        return $from->greaterThan($to)
            ? 0
            : ((int) $from->diffInDays($to)) + 1;
    }

    /** Total days a person has been out of office in a financial year. */
    public function accumulatedDaysFor(User|int $user, ?int $financialYear = null): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $financialYear ??= $this->financialYearFor();

        [$start, $end] = $this->window($financialYear);

        return $this->scopeToWindow(
            TravelRequest::query()->where('requester_id', $userId),
            $start,
            $end,
        )->get()->sum(fn (TravelRequest $tr) => $this->daysInWindow($tr, $start, $end));
    }

    /**
     * Days already accumulated plus the days a specific request would add —
     * used to warn before a trip is submitted or approved.
     */
    public function projectedDaysWith(TravelRequest $travelRequest, ?int $financialYear = null): int
    {
        $financialYear ??= $this->financialYearFor($travelRequest->b_departure_date);
        [$start, $end] = $this->window($financialYear);

        $accumulated = $this->accumulatedDaysFor($travelRequest->requester_id, $financialYear);

        // A trip that has not ended contributes nothing to the accumulated
        // total yet, so score it on its planned dates instead.
        $planned = $this->plannedDays($travelRequest, $start, $end);

        $alreadyCounted = $this->daysInWindow($travelRequest, $start, $end);

        return $accumulated - $alreadyCounted + $planned;
    }

    /** Trip length inside the window, ignoring whether the trip has ended. */
    public function plannedDays(TravelRequest $travelRequest, Carbon $windowStart, Carbon $windowEnd): int
    {
        if (! $travelRequest->b_departure_date || ! $travelRequest->b_return_date) {
            return 0;
        }

        $departure = $travelRequest->b_departure_date->copy()->startOfDay();
        $return = $travelRequest->b_return_date->copy()->startOfDay();

        $from = $departure->greaterThan($windowStart) ? $departure : $windowStart->copy();
        $to = $return->lessThan($windowEnd) ? $return : $windowEnd->copy();

        return $from->greaterThan($to)
            ? 0
            : ((int) $from->diffInDays($to)) + 1;
    }

    public function isOverLimit(int $days): bool
    {
        return $days > self::ANNUAL_LIMIT;
    }

    public function isAtLimit(int $days): bool
    {
        return $days >= self::ANNUAL_LIMIT;
    }

    public function remaining(int $days): int
    {
        return max(0, self::ANNUAL_LIMIT - $days);
    }

    /** Label such as "2026/27" for display. */
    public function label(int $financialYear): string
    {
        return $financialYear.'/'.substr((string) ($financialYear + 1), -2);
    }

    /** Approved trips overlapping the window. */
    public function scopeToWindow(Builder $query, Carbon $windowStart, Carbon $windowEnd): Builder
    {
        return $query
            ->where('status', TravelRequest::STATUS_APPROVED)
            ->whereDate('b_departure_date', '<=', $windowEnd)
            ->whereDate('b_return_date', '>=', $windowStart);
    }
}
