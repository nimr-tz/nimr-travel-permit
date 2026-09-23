<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * A request stuck pending at the final approver (DG / centre manager) whose
 * trip has already ended quietly stops blocking a new submission — see
 * TravelRequest::isOverdueAtFinalStage(). This is deliberately invisible:
 * no status change, no approval record, no wording anywhere. The DG's
 * decision, whenever it comes, works completely normally.
 */
class OverdueFinalStageRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $centreManager;
    private Unit $centre;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'director_general', 'unit_id' => null]);
        $this->centre = Unit::factory()->create(['type' => 'research_centre']);
        $this->centreManager = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'centre_manager']);
        $sup = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
        $this->user = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $sup->id]);
    }

    private function stuckAtFinalStage(array $extra = []): TravelRequest
    {
        return TravelRequest::factory()->create(array_merge([
            'requester_id'         => $this->user->id,
            'unit_id'              => $this->centre->id,
            'status'                => TravelRequest::STATUS_PENDING,
            'submitted_at'          => now()->subDays(20),
            'b_departure_date'      => now()->subDays(15),
            'b_return_date'         => now()->subDays(10),
            'current_approver_id'   => $this->centreManager->id,
            'approval_chain'        => [['stage' => 'final', 'approver_id' => $this->centreManager->id]],
        ], $extra));
    }

    private function submit(): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->post('/travel-requests', [
            'action' => 'submit',
            'b_phone' => '+255 700 000 001',
            'b_destination' => 'Arusha',
            'b_departure_date' => now()->addMonths(3)->toDateString(),
            'b_return_date' => now()->addMonths(3)->addDays(5)->toDateString(),
            'd_benefit_to_institution' => 'Shares findings.',
            'd_benefit_to_nation' => 'Informs policy.',
            'd_consequences_if_rejected' => 'Slot lost.',
            'e_govt_cost_i' => '1,200,000',
            'f_previous_travel_impact' => 'Improved throughput.',
            'g_handover_officer_name' => 'Someone',
            'g_handover_document' => UploadedFile::fake()->create('handover.pdf', 40, 'application/pdf'),
        ]);
    }

    public function test_it_does_not_block_a_new_submission_and_leaves_the_old_request_untouched(): void
    {
        $stuck = $this->stuckAtFinalStage();

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before + 1, TravelRequest::count(), 'the new request was wrongly blocked');

        $stuck->refresh();
        $this->assertSame(TravelRequest::STATUS_PENDING, $stuck->status);
        $this->assertSame($this->centreManager->id, $stuck->current_approver_id);
        $this->assertSame(0, $stuck->approvalActions()->count(), 'no approval record should have been created');
    }

    public function test_a_request_stuck_at_an_earlier_stage_still_blocks(): void
    {
        $sup = User::where('role', 'supervisor')->sole();

        $this->stuckAtFinalStage([
            'current_approver_id' => $sup->id,
            'approval_chain' => [
                ['stage' => 'supervisor', 'approver_id' => $sup->id],
                ['stage' => 'final', 'approver_id' => $this->centreManager->id],
            ],
        ]);

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before, TravelRequest::count(), 'a request stuck earlier in the chain should still block');
    }

    public function test_a_final_stage_request_whose_trip_has_not_ended_still_blocks(): void
    {
        $this->stuckAtFinalStage([
            'b_departure_date' => now()->addDays(5),
            'b_return_date' => now()->addDays(10),
        ]);

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before, TravelRequest::count(), 'a not-yet-due request should still block');
    }

    public function test_the_stuck_request_renders_with_no_special_wording(): void
    {
        $stuck = $this->stuckAtFinalStage();

        $response = $this->actingAs($this->user)->get(route('travel-requests.show', $stuck));

        $response->assertOk()
            ->assertSee(__('common.status_pending'))
            ->assertSee($this->centreManager->name);
    }
}
