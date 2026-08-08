<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\TravelDaysService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TravelReportsController extends Controller
{
    public function __construct(private TravelDaysService $travelDays) {}

    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->isDirectorGeneral() || $user->isSystemAdmin() || $user->isHr(),
            403
        );

        $currentFinancialYear = $this->travelDays->financialYearFor();
        $financialYear = filter_var($request->input('financial_year'), FILTER_VALIDATE_INT);
        $financialYear = $financialYear !== false
            && $financialYear >= 2000
            && $financialYear <= $currentFinancialYear + 1
                ? $financialYear
                : $currentFinancialYear;

        [$financialYearStart, $financialYearEnd] = $this->travelDays->window($financialYear);

        $query = $this->travelDays->scopeToWindow(
            TravelRequest::query()->with(['requester', 'unit']),
            $financialYearStart,
            $financialYearEnd,
        );

        $this->applyAccessScope($query, $user);
        $this->applyFilters($query, $request);

        $allFilteredRequests = (clone $query)->get()
            ->each(fn (TravelRequest $travelRequest) => $this->setFinancialYearDays(
                $travelRequest,
                $financialYearStart,
                $financialYearEnd,
            ));

        $people = $allFilteredRequests
            ->groupBy('requester_id')
            ->map(function ($requests) {
                /** @var TravelRequest $first */
                $first = $requests->first();

                $days = $requests->sum('financial_year_days');

                return [
                    'name' => $first->requester?->name ?? $first->b_applicant_name ?? '—',
                    'email' => $first->requester?->email,
                    'unit' => $first->unit?->name ?? '—',
                    'trips' => $requests->count(),
                    'days' => $days,
                    'over_limit' => $this->travelDays->isOverLimit($days),
                    'at_limit' => $this->travelDays->isAtLimit($days),
                    'remaining' => $this->travelDays->remaining($days),
                    'submitted' => $requests->whereNotNull('travel_report_submitted_at')->count(),
                    'missing' => $requests->whereNull('travel_report_submitted_at')
                        ->filter(fn (TravelRequest $travelRequest) => $travelRequest->b_return_date?->isBefore(today()))
                        ->count(),
                ];
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $stats = [
            'people' => $people->count(),
            'over_limit' => $people->where('over_limit', true)->count(),
            'trips' => $allFilteredRequests->count(),
            'days' => $allFilteredRequests->sum('financial_year_days'),
            'submitted' => $allFilteredRequests->whereNotNull('travel_report_submitted_at')->count(),
            'missing' => $allFilteredRequests->whereNull('travel_report_submitted_at')
                ->filter(fn (TravelRequest $travelRequest) => $travelRequest->b_return_date?->isBefore(today()))
                ->count(),
        ];

        $requests = (clone $query)
            ->orderByDesc('b_return_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(function (TravelRequest $travelRequest) use ($financialYearStart, $financialYearEnd) {
                $this->setFinancialYearDays($travelRequest, $financialYearStart, $financialYearEnd);

                return $travelRequest;
            });

        $firstTravelDate = TravelRequest::query()
            ->where('status', TravelRequest::STATUS_APPROVED)
            ->whereNotNull('b_departure_date')
            ->min('b_departure_date');
        $firstFinancialYear = $firstTravelDate
            ? ((int) substr($firstTravelDate, 5, 2) >= 7 ? (int) substr($firstTravelDate, 0, 4) : (int) substr($firstTravelDate, 0, 4) - 1)
            : $currentFinancialYear;
        $financialYears = range(max($currentFinancialYear, $firstFinancialYear), min($currentFinancialYear, $firstFinancialYear));

        $centres = Unit::query()
            ->where('type', 'research_centre')
            ->when($user->isCentreScopedViewer(), fn ($unitQuery) => $unitQuery->whereKey($user->unit_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $stations = Unit::query()
            ->when($user->isCentreScopedViewer(), fn ($unitQuery) => $unitQuery->whereKey($user->unit_id))
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $annualLimit = TravelDaysService::ANNUAL_LIMIT;

        return view('travel-reports.index', compact(
            'requests',
            'people',
            'stats',
            'centres',
            'stations',
            'financialYears',
            'financialYear',
            'annualLimit',
        ));
    }

    private function applyAccessScope(Builder $query, User $user): void
    {
        if ($user->isCentreScopedViewer()) {
            $query->where('unit_id', $user->unit_id);
        }
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($centre = $request->string('centre')->toString()) {
            if ($centre === 'hq') {
                $query->whereHas('unit', fn (Builder $unitQuery) => $unitQuery->whereIn('type', [
                    'hq_standalone',
                    'hq_directorate',
                    'hq_section',
                ]));
            } elseif (ctype_digit($centre)) {
                $query->where('unit_id', (int) $centre);
            }
        }

        if ($stationId = $request->integer('station_id')) {
            $query->where('unit_id', $stationId);
        }

        if ($reportStatus = $request->string('report_status')->toString()) {
            if ($reportStatus === 'submitted') {
                $query->whereNotNull('travel_report_submitted_at');
            } elseif ($reportStatus === 'missing') {
                $query->whereNull('travel_report_submitted_at')
                    ->whereDate('b_return_date', '<', today());
            } elseif ($reportStatus === 'not_due') {
                $query->whereNull('travel_report_submitted_at')
                    ->whereDate('b_return_date', '>=', today());
            }
        }

        if ($search = trim($request->string('search')->toString())) {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->where('request_number', 'like', "%{$search}%")
                    ->orWhere('b_applicant_name', 'like', "%{$search}%")
                    ->orWhere('b_destination', 'like', "%{$search}%")
                    ->orWhereHas('requester', fn (Builder $userQuery) => $userQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }
    }

    private function setFinancialYearDays(
        TravelRequest $travelRequest,
        Carbon $financialYearStart,
        Carbon $financialYearEnd,
    ): void {
        $travelRequest->setAttribute(
            'financial_year_days',
            $this->travelDays->daysInWindow($travelRequest, $financialYearStart, $financialYearEnd),
        );
    }
}
