<?php

namespace App\Http\Controllers;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Services\ApprovalDelegationService;
use App\Models\User;
use App\Services\ApprovalChainService;
use App\Services\SupervisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private SupervisorService $supervisors) {}

    public function __invoke(): View
    {
        $user = auth()->user();
        $user->load(['unit', 'supervisor.unit']);
        $this->supervisors->applyFixedSupervisor($user);
        $user->load(['unit', 'supervisor.unit']);

        $myRequests = TravelRequest::with(['requester', 'unit', 'currentApprover'])
            ->where('requester_id', $user->id)
            ->latest()
            ->get();

        $actedOnIds = ApprovalAction::where('actor_id', $user->id)
            ->pluck('travel_request_id');

        // Approvers this user is standing in for while they are away on approved
        // travel — the same queue /approvals shows, so the two agree.
        $actingFor = app(ApprovalDelegationService::class)->actingFor($user);

        $approvalRequests = collect();
        if (!$user->isHr() && !$user->isDirectorGeneral()) {
            $approvalRequests = TravelRequest::with(['requester', 'unit', 'currentApprover'])
                ->where('requester_id', '!=', $user->id)
                ->where(function ($q) use ($user, $actedOnIds, $actingFor) {
                    $q->where('current_approver_id', $user->id)
                        ->orWhereIn('id', $actedOnIds);

                    if ($actingFor->isNotEmpty()) {
                        $q->orWhere(function ($inner) use ($actingFor) {
                            $inner->whereIn('current_approver_id', $actingFor)
                                ->where('status', TravelRequest::STATUS_PENDING);
                        });
                    }
                })
                ->latest()
                ->get();
        }

        $allRequests = collect();
        if ($user->isHr() || $user->isDirectorGeneral()) {
            $query = TravelRequest::with(['requester', 'unit', 'currentApprover']);
            if ($user->isHr() && $user->unit?->type === 'research_centre') {
                $query->where('unit_id', $user->unit_id);
            }
            if ($user->isDirectorGeneral()) {
                $query->where(function ($q) use ($user) {
                    $q->where(function ($inner) use ($user) {
                        $inner->where('status', TravelRequest::STATUS_PENDING)
                            ->where('current_approver_id', $user->id);
                    })->orWhereIn('status', [
                        TravelRequest::STATUS_APPROVED,
                        TravelRequest::STATUS_REJECTED,
                        TravelRequest::STATUS_RETURNED,
                        TravelRequest::STATUS_CANCELLED,
                    ]);
                });
            }
            $allRequests = $query->latest()->get();
        }

        $approverIds = $actingFor->push($user->id)->all();

        $needsMyAction = ($user->isDirectorGeneral() ? $allRequests : $approvalRequests)
            ->whereIn('current_approver_id', $approverIds)
            ->where('status', 'pending');

        $statsBase = $user->isHr() || $user->isDirectorGeneral()
            ? $allRequests
            : $myRequests->merge($approvalRequests);

        $supervisorCandidates = $this->supervisors->candidatesFor($user);
        $supervisorRequired = $this->supervisors->isRequiredFor($user);

        // Roles with no supervisor step (e.g. a section head) route straight to the
        // approver derived from the unit hierarchy. Resolve who that is so the
        // dashboard can name them instead of showing an empty "no supervisor" state.
        $firstApprover = null;
        if (!$supervisorRequired && !$user->supervisor && !$user->isDirectorGeneral()) {
            try {
                $chain = app(ApprovalChainService::class)->buildChain($user);
                $firstApprover = isset($chain[0])
                    ? User::find($chain[0]['approver_id'])
                    : null;
            } catch (\RuntimeException) {
                $firstApprover = null;
            }
        }

        return view('dashboard', [
            'user' => $user,
            'myRequests' => $myRequests,
            'approvalRequests' => $approvalRequests,
            'allRequests' => $allRequests,
            'needsMyAction' => $needsMyAction,
            'supervisor' => $user->supervisor,
            'supervisorCandidates' => $supervisorCandidates,
            'supervisorRequired' => $supervisorRequired,
            'firstApprover' => $firstApprover,
            'totalRequests' => $statsBase->count(),
            'pendingCount' => $statsBase->where('status', 'pending')->count(),
            'approvedCount' => $statsBase->where('status', 'approved')->count(),
            'rejectedCount' => $statsBase->where('status', 'rejected')->count(),
        ]);
    }

    public function updateSupervisor(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->isDirectorGeneral(), 403);

        if ($this->supervisors->applyFixedSupervisor($user)) {
            return redirect()->route('dashboard')
                ->with('status', __('dashboard.supervisor_updated'));
        }

        $validated = $request->validate([
            'supervisor_id' => ['nullable', 'integer'],
        ]);

        $supervisorId = $validated['supervisor_id'] ?? null;

        if (!$supervisorId) {
            $user->forceFill(['supervisor_id' => null])->save();

            return redirect()->route('dashboard')
                ->with('status', __('dashboard.supervisor_updated'));
        }

        if (!$this->supervisors->isValidCandidate((int) $supervisorId, $user->unit, $user->role, $user->id)) {
            throw ValidationException::withMessages([
                'supervisor_id' => __('dashboard.supervisor_invalid'),
            ]);
        }

        $user->forceFill(['supervisor_id' => (int) $supervisorId])->save();

        return redirect()->route('dashboard')
            ->with('status', __('dashboard.supervisor_updated'));
    }
}
