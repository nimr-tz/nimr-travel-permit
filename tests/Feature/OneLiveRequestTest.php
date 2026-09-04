<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OneLiveRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Unit $centre;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'director_general', 'unit_id' => null]);
        $this->centre = Unit::factory()->create(['type' => 'research_centre']);
        $cm = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'centre_manager']);
        $sup = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $cm->id]);
        $this->user = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'staff', 'supervisor_id' => $sup->id]);
    }

    private function existing(string $status, array $extra = []): TravelRequest
    {
        return TravelRequest::factory()->create(array_merge([
            'requester_id' => $this->user->id,
            'unit_id' => $this->centre->id,
            'status' => $status,
            'b_departure_date' => now()->addDays(20),
            'b_return_date' => now()->addDays(25),
            'travel_report_submitted_at' => null,
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

    public function test_a_future_dated_approved_trip_blocks_stacking_another(): void
    {
        // The reported loophole: approved, returns well in the future, so no
        // report is due yet — this must still block a second request.
        $this->existing(TravelRequest::STATUS_APPROVED);

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before, TravelRequest::count(), 'a second request was stacked behind a future-dated approved trip');
    }

    public function test_a_pending_request_blocks_another(): void
    {
        $this->existing(TravelRequest::STATUS_PENDING);

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before, TravelRequest::count(), 'a second request was stacked behind a pending one');
    }

    public function test_ten_at_once_cannot_be_stacked(): void
    {
        $before = TravelRequest::count();

        for ($i = 0; $i < 10; $i++) {
            $this->submit();
        }

        $this->assertSame($before + 1, TravelRequest::count(), 'more than one live request got through');
    }

    public function test_resolved_requests_never_block(): void
    {
        $this->existing(TravelRequest::STATUS_REJECTED);
        $this->existing(TravelRequest::STATUS_CANCELLED);
        $this->existing(TravelRequest::STATUS_DRAFT);
        $this->existing(TravelRequest::STATUS_APPROVED, [
            'b_departure_date' => now()->subDays(30),
            'b_return_date' => now()->subDays(20),
            'travel_report_submitted_at' => now()->subDays(5),
        ]);

        $before = TravelRequest::count();
        $this->submit();

        $this->assertSame($before + 1, TravelRequest::count(), 'a resolved or reported request wrongly blocked a new one');
    }

    public function test_a_report_cannot_be_filed_before_the_trip_ends(): void
    {
        $future = $this->existing(TravelRequest::STATUS_APPROVED);

        $this->actingAs($this->user)
            ->post("/travel-requests/{$future->id}/report", [
                'travel_report_document' => UploadedFile::fake()->create('report.pdf', 40, 'application/pdf'),
                'report_submission_confirmed' => '1',
            ])
            ->assertForbidden();

        $this->assertNull($future->fresh()->travel_report_submitted_at);
    }

    public function test_a_report_may_be_filed_once_the_trip_has_ended(): void
    {
        $ended = $this->existing(TravelRequest::STATUS_APPROVED, [
            'b_departure_date' => now()->subDays(10),
            'b_return_date' => now()->subDays(2),
        ]);

        $this->actingAs($this->user)
            ->post("/travel-requests/{$ended->id}/report", [
                'travel_report_document' => UploadedFile::fake()->create('report.pdf', 40, 'application/pdf'),
                'report_submission_confirmed' => '1',
            ])->assertRedirect();

        $this->assertNotNull($ended->fresh()->travel_report_submitted_at);
    }
}
