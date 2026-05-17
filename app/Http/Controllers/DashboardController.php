<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        // My own requests
        $myRequests = TravelRequest::with(['requester', 'unit', 'currentApprover'])
            ->where('requester_id', $user->id)
            ->latest()->get();

        // Requests I need to act on OR have already acted on (approval queue)
        $actedOnIds = \App\Models\ApprovalAction::where('actor_id', $user->id)
            ->pluck('travel_request_id');

        $approvalRequests = collect();
        if (!$user->isHr() && !$user->isDirectorGeneral()) {
            $approvalRequests = TravelRequest::with(['requester', 'unit', 'currentApprover'])
                ->where('requester_id', '!=', $user->id)
                ->where(function ($q) use ($user, $actedOnIds) {
                    $q->where('current_approver_id', $user->id)
                      ->orWhereIn('id', $actedOnIds);
                })
                ->latest()->get();
        }

        // HR and DG see all
        $allRequests = collect();
        if ($user->isHr() || $user->isDirectorGeneral()) {
            $query = TravelRequest::with(['requester', 'unit', 'currentApprover']);
            if ($user->isHr() && $user->unit?->type === 'research_centre') {
                $query->where('unit_id', $user->unit_id);
            }
            $allRequests = $query->latest()->get();
        }

        $needsMyAction = ($user->isDirectorGeneral() ? $allRequests : $approvalRequests)
            ->where('current_approver_id', $user->id)
            ->where('status', 'pending');

        $statsBase = $user->isHr() || $user->isDirectorGeneral() ? $allRequests : $myRequests->merge($approvalRequests);

        return view('dashboard', [
            'user'             => $user,
            'myRequests'       => $myRequests,
            'approvalRequests' => $approvalRequests,
            'allRequests'      => $allRequests,
            'needsMyAction'    => $needsMyAction,
            'totalRequests'    => $statsBase->count(),
            'pendingCount'     => $statsBase->where('status', 'pending')->count(),
            'approvedCount'    => $statsBase->where('status', 'approved')->count(),
            'rejectedCount'    => $statsBase->where('status', 'rejected')->count(),
        ]);
    }
}
