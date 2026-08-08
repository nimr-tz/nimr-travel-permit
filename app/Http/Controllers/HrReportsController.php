<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class HrReportsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->authorizeViewer($request);

        $query = TravelRequest::with(['requester', 'unit'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        $this->applyAccessScope($query, $user);
        $this->applyFilters($query, $request);

        $requests = $query->paginate(20)->withQueryString();

        // Summary stats — unfiltered totals for the overview cards, but always
        // within the viewer's access scope.
        $statsBase = fn () => $this->applyAccessScope(TravelRequest::query(), $user);

        $stats = [
            'total'    => $statsBase()->whereNotNull('submitted_at')->count(),
            'pending'  => $statsBase()->where('status', TravelRequest::STATUS_PENDING)->count(),
            'approved' => $statsBase()->where('status', TravelRequest::STATUS_APPROVED)->count(),
            'rejected' => $statsBase()->where('status', TravelRequest::STATUS_REJECTED)->count(),
            'returned' => $statsBase()->where('status', TravelRequest::STATUS_RETURNED)->count(),
        ];

        $units = Unit::query()
            ->when($user->isCentreScopedViewer(), fn ($unitQuery) => $unitQuery->whereKey($user->unit_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $statuses = TravelRequest::STATUS_LABELS;

        return view('hr.reports.index', compact('requests', 'stats', 'units', 'statuses'));
    }

    public function export(Request $request): Response
    {
        $user = $this->authorizeViewer($request);

        $query = TravelRequest::with(['requester', 'unit'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        $this->applyAccessScope($query, $user);
        $this->applyFilters($query, $request);

        $rows = $query->get();

        $filename = 'travel-requests-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $csv = fopen('php://output', 'w');
        ob_start();

        fputcsv($csv, [
            'Request No.', 'Applicant', 'Unit', 'Destination',
            'Departure', 'Return', 'Status', 'Submitted At',
        ]);

        foreach ($rows as $tr) {
            fputcsv($csv, array_map($this->csvSafe(...), [
                $tr->request_number,
                $tr->b_applicant_name ?? $tr->requester?->name,
                $tr->unit?->name,
                $tr->b_destination,
                $tr->b_departure_date?->format('d/m/Y'),
                $tr->b_return_date?->format('d/m/Y'),
                $tr->statusLabel(),
                $tr->submitted_at?->format('d/m/Y H:i'),
            ]));
        }

        fclose($csv);
        $content = ob_get_clean();

        return response($content, 200, $headers);
    }

    private function authorizeViewer(Request $request): User
    {
        $user = $request->user();

        abort_unless($user->isHr() || $user->isDirectorGeneral(), 403);

        return $user;
    }

    /**
     * A centre HR officer sees only their own centre; HQ HR and the DG see
     * the whole institute.
     */
    private function applyAccessScope(Builder $query, User $user): Builder
    {
        if ($user->isCentreScopedViewer()) {
            $query->where('unit_id', $user->unit_id);
        }

        return $query;
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($unitId = $request->input('unit_id')) {
            $query->where('unit_id', $unitId);
        }

        if ($search = $request->input('search')) {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery->where('request_number', 'like', "%{$search}%")
                    ->orWhere('b_applicant_name', 'like', "%{$search}%")
                    ->orWhere('b_destination', 'like', "%{$search}%");
            });
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('submitted_at', '>=', $from);
        }

        if ($to = $request->input('date_to')) {
            $query->whereDate('submitted_at', '<=', $to);
        }
    }

    /**
     * Neutralise spreadsheet formula injection. Excel/Sheets execute any cell
     * beginning with = + - @ or a leading tab/CR, so prefix those with a
     * single quote to force literal text.
     */
    private function csvSafe(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
