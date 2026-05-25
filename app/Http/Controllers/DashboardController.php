<?php

namespace App\Http\Controllers;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user();
        $user->load(['unit', 'supervisor.unit']);

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

        // HR and DG see an org-wide view — but DG only sees what is relevant to them.
        $allRequests = collect();
        if ($user->isHr() || $user->isDirectorGeneral()) {
            $query = TravelRequest::with(['requester', 'unit', 'currentApprover']);
            if ($user->isHr() && $user->unit?->type === 'research_centre') {
                $query->where('unit_id', $user->unit_id);
            }
            if ($user->isDirectorGeneral()) {
                // Show DG: pending requests at their stage + all resolved/returned requests.
                // Exclude drafts and requests still pending at a lower stage.
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

        $needsMyAction = ($user->isDirectorGeneral() ? $allRequests : $approvalRequests)
            ->where('current_approver_id', $user->id)
            ->where('status', 'pending');

        $statsBase = $user->isHr() || $user->isDirectorGeneral() ? $allRequests : $myRequests->merge($approvalRequests);

        $supervisorCandidates = $this->supervisorCandidatesFor($user);
        // Staff at a research centre must set a supervisor before submitting.
        $supervisorRequired = $user->unit?->type === 'research_centre'
            && $user->role === 'staff';

        return view('dashboard', [
            'user'                 => $user,
            'myRequests'           => $myRequests,
            'approvalRequests'     => $approvalRequests,
            'allRequests'          => $allRequests,
            'needsMyAction'        => $needsMyAction,
            'supervisor'           => $user->supervisor,
            'supervisorCandidates' => $supervisorCandidates,
            'supervisorRequired'   => $supervisorRequired,
            'totalRequests'        => $statsBase->count(),
            'pendingCount'         => $statsBase->where('status', 'pending')->count(),
            'approvedCount'        => $statsBase->where('status', 'approved')->count(),
            'rejectedCount'        => $statsBase->where('status', 'rejected')->count(),
        ]);
    }

    public function updateSupervisor(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_if($user->isHr() || $user->isDirectorGeneral(), 403);

        $validated = $request->validate([
            'supervisor_id' => ['nullable', 'integer'],
        ]);

        $supervisorId = $validated['supervisor_id'] ?? null;

        if (!$supervisorId) {
            $user->forceFill(['supervisor_id' => null])->save();

            return redirect()->route('dashboard')
                ->with('status', __('dashboard.supervisor_updated'));
        }

        $candidateIds = $this->supervisorCandidatesFor($user)->pluck('id')->all();

        if (!in_array((int) $supervisorId, $candidateIds, true)) {
            throw ValidationException::withMessages([
                'supervisor_id' => __('dashboard.supervisor_invalid'),
            ]);
        }

        $user->forceFill(['supervisor_id' => (int) $supervisorId])->save();

        return redirect()->route('dashboard')
            ->with('status', __('dashboard.supervisor_updated'));
    }

    private function supervisorCandidatesFor(User $user): Collection
    {
        if (!$user->unit_id || !$user->unit) {
            return new Collection();
        }

        // Research centre staff can pick any active colleague in their centre
        // except the centre manager (who is the final approver), DG, and HR.
        if ($user->unit->type === 'research_centre') {
            return User::query()
                ->where('unit_id', $user->unit_id)
                ->where('id', '!=', $user->id)
                ->where('is_active', true)
                ->whereNotIn('role', ['centre_manager', 'director_general', 'hr'])
                ->orderBy('name')
                ->get();
        }

        $roles = match ($user->unit->type) {
            'hq_standalone'  => ['manager'],
            'hq_section'     => ['head', 'manager'],
            'hq_directorate' => ['director'],
            default          => [],
        };

        if ($roles === []) {
            return new Collection();
        }

        return User::query()
            ->where('unit_id', $user->unit_id)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->whereIn('role', $roles)
            ->orderBy('name')
            ->get();
    }
}
