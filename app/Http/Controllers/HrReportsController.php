<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HrReportsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->isHr() || $request->user()->isDirectorGeneral(), 403);

        $query = TravelRequest::with(['requester', 'unit'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        // Filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($unitId = $request->input('unit_id')) {
            $query->where('unit_id', $unitId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
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

        $requests = $query->paginate(20)->withQueryString();

        // Summary stats (unfiltered totals for the overview cards)
        $stats = [
            'total'    => TravelRequest::whereNotNull('submitted_at')->count(),
            'pending'  => TravelRequest::where('status', TravelRequest::STATUS_PENDING)->count(),
            'approved' => TravelRequest::where('status', TravelRequest::STATUS_APPROVED)->count(),
            'rejected' => TravelRequest::where('status', TravelRequest::STATUS_REJECTED)->count(),
            'returned' => TravelRequest::where('status', TravelRequest::STATUS_RETURNED)->count(),
        ];

        $units    = Unit::orderBy('name')->get(['id', 'name']);
        $statuses = TravelRequest::STATUS_LABELS;

        return view('hr.reports.index', compact('requests', 'stats', 'units', 'statuses'));
    }
}
