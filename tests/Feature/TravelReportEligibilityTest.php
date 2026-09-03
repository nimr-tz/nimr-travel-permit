<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelReportEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_trip_without_final_report_blocks_create_and_direct_store(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $supervisor = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $requester = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $previousTrip = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'b_return_date' => today()->subDay(),
        ]);

        $this->actingAs($requester)
            ->get(route('travel-requests.create'))
            ->assertRedirect(route('travel-requests.show', $previousTrip))
            ->assertSessionHas('error');

        $this->actingAs($requester)
            ->post(route('travel-requests.store'))
            ->assertRedirect(route('travel-requests.show', $previousTrip))
            ->assertSessionHas('error');
    }

    public function test_final_report_clears_new_request_block(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $supervisor = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $requester = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
        ]);
        TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'b_return_date' => today()->subDay(),
            'travel_report_document' => 'travel-reports/final.pdf',
            'travel_report_original_name' => 'final.pdf',
            'travel_report_submitted_at' => now(),
        ]);

        $this->actingAs($requester)
            ->get(route('travel-requests.create'))
            ->assertOk();
    }

    /**
     * Previously an approved trip that had not yet returned let a second
     * request through, because no report was owed until the return date
     * passed. That made the report requirement optional in practice: a
     * traveller could stack any number of future-dated trips before a single
     * report came due. An approved trip now blocks the next request until it
     * has been travelled and reported on.
     */
    public function test_trip_that_has_not_returned_still_blocks_a_new_request(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $supervisor = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $requester = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $approved = TravelRequest::factory()->approved()->create([
            'requester_id' => $requester->id,
            'unit_id' => $unit->id,
            'b_return_date' => today()->addDay(),
        ]);

        $this->actingAs($requester)
            ->get(route('travel-requests.create'))
            ->assertRedirect(route('travel-requests.show', $approved));
    }
}
