<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Services\ApprovalDelegationService;
use Illuminate\View\View;

class ApprovalsController extends Controller
{
    public function __invoke(ApprovalDelegationService $delegation): View
    {
        $user = auth()->user();

        // Approvers this user is standing in for while they are away on
        // approved travel. Their queue shows up here without anything being
        // reassigned, and lapses on its own the day they return.
        $actingFor = $delegation->actingFor($user);

        $actedOnIds = ApprovalAction::where('actor_id', $user->id)
            ->pluck('travel_request_id');

        $pending = TravelRequest::with(['requester', 'unit', 'currentApprover'])
            ->where('requester_id', '!=', $user->id)
            ->where(function ($q) use ($user, $actingFor) {
                $q->where('current_approver_id', $user->id);

                if ($actingFor->isNotEmpty()) {
                    $q->orWhereIn('current_approver_id', $actingFor);
                }
            })
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

        return view('approvals.index', compact('user', 'pending', 'history', 'actingFor'));
    }
}
