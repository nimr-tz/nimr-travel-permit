<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\ApprovalDelegationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalDelegationTest extends TestCase
{
    use RefreshDatabase;

    private Unit $centre;
    private User $centreManager;
    private User $supervisor;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'director_general', 'unit_id' => null]);
        $this->centre = Unit::factory()->create(['type' => 'research_centre']);
        $this->centreManager = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'centre_manager']);
        $this->supervisor = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
        $this->staff = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $this->supervisor->id]);
    }

    /** Approved travel covering today, handed over to $officer. */
    private function sendOnTravel(User $traveller, ?User $officer): TravelRequest
    {
        return TravelRequest::factory()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $traveller->unit_id,
            'status' => TravelRequest::STATUS_APPROVED,
            'b_departure_date' => now()->subDays(2),
            'b_return_date' => now()->addDays(5),
            'g_handover_officer_id' => $officer?->id,
            'g_handover_officer_name' => $officer?->name,
        ]);
    }

    private function pendingFor(User $approver, User $requester): TravelRequest
    {
        return TravelRequest::factory()->create([
            'requester_id' => $requester->id,
            'unit_id' => $requester->unit_id,
            'status' => TravelRequest::STATUS_PENDING,
            'current_approver_id' => $approver->id,
            'approval_chain' => [['approver_id' => $approver->id, 'stage' => 'supervisor']],
        ]);
    }

    public function test_a_delegate_may_act_while_the_approver_is_away(): void
    {
        $standIn = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
        $this->sendOnTravel($this->supervisor, $standIn);
        $request = $this->pendingFor($this->supervisor, $this->staff);

        $this->assertTrue(app(ApprovalDelegationService::class)->mayActOn($request, $standIn));

        $this->actingAs($standIn)
            ->post("/travel-requests/{$request->id}/approve", ['decision' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseHas('approval_actions', [
            'travel_request_id' => $request->id,
            'actor_id' => $standIn->id,
            'decision' => 'approved',
        ]);
    }

    public function test_authority_never_travels_downward_to_ordinary_staff(): void
    {
        $junior = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $this->supervisor->id]);
        $this->sendOnTravel($this->centreManager, $junior);
        $request = $this->pendingFor($this->centreManager, $this->staff);

        $this->assertNull(app(ApprovalDelegationService::class)->delegateFor($this->centreManager));
        $this->assertFalse(app(ApprovalDelegationService::class)->mayActOn($request, $junior));

        $this->actingAs($junior)
            ->post("/travel-requests/{$request->id}/approve", ['decision' => 'approved'])
            ->assertForbidden();
    }

    public function test_a_delegate_may_not_approve_their_own_request(): void
    {
        $standIn = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
        $this->sendOnTravel($this->supervisor, $standIn);
        $ownRequest = $this->pendingFor($this->supervisor, $standIn);

        $this->assertFalse(app(ApprovalDelegationService::class)->mayActOn($ownRequest, $standIn));

        $this->actingAs($standIn)
            ->post("/travel-requests/{$ownRequest->id}/approve", ['decision' => 'approved'])
            ->assertForbidden();
    }

    public function test_delegation_does_not_apply_before_or_after_the_travel_dates(): void
    {
        $standIn = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);

        TravelRequest::factory()->create([
            'requester_id' => $this->supervisor->id,
            'unit_id' => $this->centre->id,
            'status' => TravelRequest::STATUS_APPROVED,
            'b_departure_date' => now()->addDays(10),
            'b_return_date' => now()->addDays(20),
            'g_handover_officer_id' => $standIn->id,
        ]);

        $this->assertNull(app(ApprovalDelegationService::class)->delegateFor($this->supervisor),
            'delegation started before the traveller left');
    }

    public function test_unapproved_travel_does_not_hand_over_anything(): void
    {
        $standIn = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);

        TravelRequest::factory()->create([
            'requester_id' => $this->supervisor->id,
            'unit_id' => $this->centre->id,
            'status' => TravelRequest::STATUS_PENDING,
            'b_departure_date' => now()->subDay(),
            'b_return_date' => now()->addDays(3),
            'g_handover_officer_id' => $standIn->id,
        ]);

        $this->assertNull(app(ApprovalDelegationService::class)->delegateFor($this->supervisor),
            'a merely pending trip delegated authority');
    }

    public function test_the_delegate_sees_the_queue_without_anything_being_reassigned(): void
    {
        $standIn = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
        $this->sendOnTravel($this->supervisor, $standIn);
        $request = $this->pendingFor($this->supervisor, $this->staff);

        $ids = $this->actingAs($standIn)->get('/approvals')->assertOk()->viewData('pending')->pluck('id');
        $this->assertContains($request->id, $ids->all(), 'the delegate could not see the queue');

        $request->refresh();
        $this->assertSame($this->supervisor->id, $request->current_approver_id,
            'current_approver_id was rewritten — the audit record of whose decision this is must not move');
    }
}
