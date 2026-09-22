<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * The overdue-approval fix must not depend on any scheduled command actually
 * running — it resolves the moment a stuck request is looked at or the
 * moment its owner tries to submit a new one. No artisan call in this file.
 */
class OverdueApprovalResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewing_a_stuck_request_resolves_it_without_any_command_running(): void
    {
        Notification::fake();

        $centre = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centre->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $stuck = TravelRequest::factory()->pending()->create([
            'requester_id'        => $staff->id,
            'unit_id'             => $centre->id,
            'b_departure_date'    => now()->subDays(20)->toDateString(),
            'b_return_date'       => now()->subDays(15)->toDateString(),
            'current_approver_id' => $centreManager->id,
            'approval_chain'      => [['stage' => 'final', 'approver_id' => $centreManager->id]],
        ]);

        $this->actingAs($staff)->get(route('travel-requests.show', $stuck))->assertOk();

        $stuck->refresh();
        $this->assertSame(TravelRequest::STATUS_APPROVED, $stuck->status);
        $this->assertNull($stuck->current_approver_id);
    }

    public function test_trying_to_submit_a_new_request_resolves_the_stuck_one_and_still_requires_its_report(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centre->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $stuck = TravelRequest::factory()->pending()->create([
            'requester_id'        => $staff->id,
            'unit_id'             => $centre->id,
            'b_departure_date'    => now()->subDays(20)->toDateString(),
            'b_return_date'       => now()->subDays(15)->toDateString(),
            'current_approver_id' => $centreManager->id,
            'approval_chain'      => [['stage' => 'final', 'approver_id' => $centreManager->id]],
        ]);

        $response = $this->actingAs($staff)->get(route('travel-requests.create'));

        $stuck->refresh();
        $this->assertSame(TravelRequest::STATUS_APPROVED, $stuck->status, 'visiting create should have resolved the stuck request');

        // Still blocked — but now for the real reason (missing report), not
        // stuck behind a decision nobody was going to make.
        $response->assertRedirect(route('travel-requests.show', $stuck))
            ->assertSessionHas('error', __('travel.report_required_before_new_request'));
    }

    public function test_a_request_not_yet_due_or_not_at_the_final_stage_is_left_pending_on_view(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $centre->id]);
        $supervisor = User::factory()->supervisor()->create(['unit_id' => $centre->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $notYetDue = TravelRequest::factory()->pending()->create([
            'requester_id'        => $staff->id,
            'unit_id'             => $centre->id,
            'b_departure_date'    => now()->addDays(5)->toDateString(),
            'b_return_date'       => now()->addDays(10)->toDateString(),
            'current_approver_id' => $centreManager->id,
            'approval_chain'      => [['stage' => 'final', 'approver_id' => $centreManager->id]],
        ]);

        $stuckEarlier = TravelRequest::factory()->pending()->create([
            'requester_id'        => $staff->id,
            'unit_id'             => $centre->id,
            'b_departure_date'    => now()->subDays(20)->toDateString(),
            'b_return_date'       => now()->subDays(15)->toDateString(),
            'current_approver_id' => $supervisor->id,
            'approval_chain'      => [
                ['stage' => 'supervisor', 'approver_id' => $supervisor->id],
                ['stage' => 'final', 'approver_id' => $centreManager->id],
            ],
        ]);

        $this->actingAs($staff)->get(route('travel-requests.show', $notYetDue))->assertOk();
        $this->actingAs($staff)->get(route('travel-requests.show', $stuckEarlier))->assertOk();

        $this->assertSame(TravelRequest::STATUS_PENDING, $notYetDue->fresh()->status);
        $this->assertSame(TravelRequest::STATUS_PENDING, $stuckEarlier->fresh()->status);
    }
}
