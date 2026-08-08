<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\TravelRequest;
use App\Models\User;
use App\Services\SessionRevocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $request->user()->loadMissing(['unit', 'supervisor']);

        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill(collect($validated)->only(['name', 'email', 'phone', 'job_title'])->toArray());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }

            $user->avatar_path = $request->file('avatar')->store('avatars', 'public');
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Close the user's account.
     *
     * Accounts are deactivated rather than deleted. A travel request carries a
     * requester, an approver and a signed approval history; hard-deleting the
     * user nulls those foreign keys, which would strand pending requests and
     * erase the attribution on an official approval record.
     */
    public function destroy(Request $request, SessionRevocationService $sessions): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($blocker = $this->outstandingWorkflowObligation($user)) {
            return Redirect::route('profile.edit')->withErrors(
                ['closure' => $blocker],
                'userDeletion',
            );
        }

        $user->forceFill(['is_active' => false])->save();
        $sessions->revokeAllFor($user);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/')->with('status', __('common.profile_closed'));
    }

    /**
     * Work that must be handed over before an account can be closed.
     */
    private function outstandingWorkflowObligation(User $user): ?string
    {
        $awaitingDecision = TravelRequest::where('current_approver_id', $user->getKey())
            ->where('status', TravelRequest::STATUS_PENDING)
            ->count();

        if ($awaitingDecision > 0) {
            return __('common.profile_close_blocked_approvals', ['count' => $awaitingDecision]);
        }

        $openRequests = TravelRequest::where('requester_id', $user->getKey())
            ->whereIn('status', [
                TravelRequest::STATUS_PENDING,
                TravelRequest::STATUS_RETURNED,
            ])
            ->count();

        if ($openRequests > 0) {
            return __('common.profile_close_blocked_requests', ['count' => $openRequests]);
        }

        return null;
    }
}
