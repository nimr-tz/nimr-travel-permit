<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestListVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_hq_system_admin_sees_every_request_across_units_except_drafts(): void
    {
        $hq = Unit::factory()->hqStandalone()->create();
        $centre = Unit::factory()->researchCentre()->create();

        $hqAdmin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);

        $pending = TravelRequest::factory()->pending()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $centre->id])->id,
            'unit_id'          => $centre->id,
            'b_applicant_name' => 'Pending Traveller',
        ]);
        $approved = TravelRequest::factory()->approved()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $hq->id])->id,
            'unit_id'          => $hq->id,
            'b_applicant_name' => 'Approved Traveller',
        ]);
        $draft = TravelRequest::factory()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $hq->id])->id,
            'unit_id'          => $hq->id,
            'b_applicant_name' => 'Draft Traveller',
        ]);

        $response = $this->actingAs($hqAdmin)->get(route('travel-requests.index'));

        $response->assertOk()
            ->assertSee('Pending Traveller')
            ->assertSee('Approved Traveller')
            ->assertDontSee('Draft Traveller');
    }

    public function test_centre_system_admin_only_sees_their_own_centre(): void
    {
        $ownCentre = Unit::factory()->researchCentre()->create();
        $otherCentre = Unit::factory()->researchCentre()->create();

        $centreAdmin = User::factory()->systemAdmin()->create(['unit_id' => $ownCentre->id]);

        $inCentre = TravelRequest::factory()->pending()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $ownCentre->id])->id,
            'unit_id'          => $ownCentre->id,
            'b_applicant_name' => 'Own Centre Traveller',
        ]);
        $elsewhere = TravelRequest::factory()->pending()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $otherCentre->id])->id,
            'unit_id'          => $otherCentre->id,
            'b_applicant_name' => 'Other Centre Traveller',
        ]);

        $this->actingAs($centreAdmin)->get(route('travel-requests.index'))
            ->assertOk()
            ->assertSee('Own Centre Traveller')
            ->assertDontSee('Other Centre Traveller');
    }

    public function test_hr_sees_only_approved_requests(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $hr = User::factory()->hr()->create(['unit_id' => $unit->id]);

        $approved = TravelRequest::factory()->approved()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $unit->id])->id,
            'unit_id'          => $unit->id,
            'b_applicant_name' => 'Approved For HR',
        ]);
        $pending = TravelRequest::factory()->pending()->create([
            'requester_id'     => User::factory()->staff()->create(['unit_id' => $unit->id])->id,
            'unit_id'          => $unit->id,
            'b_applicant_name' => 'Pending For HR',
        ]);

        $this->actingAs($hr)->get(route('travel-requests.index'))
            ->assertOk()
            ->assertSee('Approved For HR')
            ->assertDontSee('Pending For HR');
    }
}
