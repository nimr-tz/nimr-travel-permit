<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use Illuminate\View\View;

class ApprovalsController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();

        $actedOnIds = ApprovalAction::where('actor_id', $user->id)
            ->pluck('travel_request_id');

        $pending = TravelRequest::with(['requester', 'unit', 'currentApprover'])
            ->where('requester_id', '!=', $user->id)
            ->where('current_approver_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $history = TravelRequest::with(['requester', 'unit', 'currentApprover', 'approvalActions' => fn($q) => $q->where('actor_id', $user->id)->latest()])
            ->where('requester_id', '!=', $user->id)
            ->whereIn('id', $actedOnIds)
            ->where(function ($q) use ($user) {
                $q->where('current_approver_id', '!=', $user->id)
                  ->orWhere('status', '!=', 'pending');
            })
            ->latest()
            ->get();

        return view('approvals.index', compact('user', 'pending', 'history'));
    }
}
