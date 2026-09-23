<?php

namespace Tests\Feature;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The final approver (DG / centre manager) must never appear to have
 * personally approved a request the system auto-approved on their behalf —
 * and must still be able to record a real decision on it afterward. See
 * OverdueApprovalResolver for how a request gets into this state.
 */
class AutoApprovalConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private function autoApprovedRequest(User $centreManager, User $staff, Unit $unit): TravelRequest
    {
        $request = TravelRequest::factory()->approved()->create([
            'requester_id'    => $staff->id,
            'unit_id'         => $unit->id,
            'b_departure_date' => now()->subDays(20)->toDateString(),
            'b_return_date'    => now()->subDays(15)->toDateString(),
        ]);

        ApprovalAction::create([
            'travel_request_id' => $request->id,
            'actor_id'           => null,
            'stage'              => 'final',
            'decision'           => 'approved',
            'comment'            => 'Auto-approved: return date passed without a decision from the final approver.',
            'acted_at'           => now()->subDays(1),
        ]);

        return $request;
    }

    public function test_the_web_page_does_not_attribute_the_decision_to_the_assigned_approver(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id, 'name' => 'Said Aboud']);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->autoApprovedRequest($centreManager, $staff, $unit);
        $request->update(['approval_chain' => [['stage' => 'final', 'approver_id' => $centreManager->id]]]);

        $response = $this->actingAs($staff)->withSession(['locale' => 'en'])
            ->get(route('travel-requests.show', $request));

        $response->assertOk()
            ->assertSee(__('travel.auto_approved_system_label'))
            ->assertSee(__('travel.auto_approved_banner_title'));
    }

    public function test_the_final_approver_can_confirm_the_auto_approval(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->autoApprovedRequest($centreManager, $staff, $unit);
        $request->update(['approval_chain' => [['stage' => 'final', 'approver_id' => $centreManager->id]]]);

        $this->assertTrue($request->fresh()->load('approvalActions')->finalStageAutoApproved());

        $this->actingAs($centreManager)
            ->post(route('travel-requests.confirm-auto-approval', $request), ['comment' => 'Confirmed, this was a legitimate trip.'])
            ->assertRedirect(route('travel-requests.show', $request));

        $request->refresh()->load('approvalActions');
        $this->assertFalse($request->finalStageAutoApproved());
        $this->assertSame(TravelRequest::STATUS_APPROVED, $request->status);

        $latest = $request->approvalActions->where('stage', 'final')->sortByDesc('acted_at')->first();
        $this->assertSame($centreManager->id, $latest->actor_id);
        $this->assertSame('approved', $latest->decision);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'travel_request.auto_approval_confirmed',
            'actor_id'   => $centreManager->id,
            'subject_id' => $request->id,
        ]);
    }

    public function test_someone_who_is_not_the_final_approver_cannot_confirm(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $stranger = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->autoApprovedRequest($centreManager, $staff, $unit);
        $request->update(['approval_chain' => [['stage' => 'final', 'approver_id' => $centreManager->id]]]);

        $this->actingAs($stranger)
            ->post(route('travel-requests.confirm-auto-approval', $request))
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('travel-requests.confirm-auto-approval', $request))
            ->assertForbidden();
    }

    public function test_the_printable_permit_does_not_show_a_blank_signed_stamp_for_the_auto_approval(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->autoApprovedRequest($centreManager, $staff, $unit);
        $request->update(['approval_chain' => [['stage' => 'final', 'approver_id' => $centreManager->id]]]);

        $print = $this->actingAs($staff)->get(route('travel-requests.print', $request));
        $print->assertOk()->assertSee('KIOTOMATIKI');

        $this->actingAs($staff)->get(route('travel-requests.pdf', $request))->assertOk();
    }

    public function test_confirming_removes_the_confirmation_prompt_from_the_page(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id, 'name' => 'Said Aboud']);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->autoApprovedRequest($centreManager, $staff, $unit);
        $request->update(['approval_chain' => [['stage' => 'final', 'approver_id' => $centreManager->id]]]);

        $this->actingAs($centreManager)->post(route('travel-requests.confirm-auto-approval', $request));

        $response = $this->actingAs($centreManager)->withSession(['locale' => 'en'])
            ->get(route('travel-requests.show', $request));

        $response->assertOk()
            ->assertDontSee(__('travel.auto_approved_banner_title'))
            ->assertSee('Said Aboud');
    }
}
