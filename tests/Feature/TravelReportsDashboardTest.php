<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TravelReportsDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_director_general_sees_financial_year_travel_days_and_report_totals(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $centre = Unit::factory()->researchCentre()->create(['name' => 'Amani Research Centre']);
        $traveller = User::factory()->staff()->create([
            'name' => 'Annual Traveller',
            'unit_id' => $centre->id,
        ]);

        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2025-07-01',
            'b_return_date' => '2025-07-03',
            'travel_report_document' => 'travel-reports/first.pdf',
            'travel_report_original_name' => 'first.pdf',
            'travel_report_submitted_at' => '2025-07-04 08:00:00',
        ]);
        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2026-06-29',
            'b_return_date' => '2026-06-30',
        ]);

        $response = $this->actingAs($dg)->get(route('travel-reports.index', [
            'financial_year' => 2025,
        ]));

        $response->assertOk()
            ->assertSee('Annual Traveller')
            ->assertViewHas('stats', fn (array $stats) => $stats['people'] === 1
                && $stats['trips'] === 2
                && $stats['days'] === 5
                && $stats['submitted'] === 1
                && $stats['missing'] === 1)
            ->assertViewHas('people', fn ($people) => $people->first()['days'] === 5);
    }

    public function test_centre_filter_limits_report_results(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $firstCentre = Unit::factory()->researchCentre()->create(['name' => 'First Centre']);
        $secondCentre = Unit::factory()->researchCentre()->create(['name' => 'Second Centre']);
        $firstTraveller = User::factory()->staff()->create(['name' => 'First Traveller', 'unit_id' => $firstCentre->id]);
        $secondTraveller = User::factory()->staff()->create(['name' => 'Second Traveller', 'unit_id' => $secondCentre->id]);

        foreach ([[$firstTraveller, $firstCentre], [$secondTraveller, $secondCentre]] as [$traveller, $centre]) {
            TravelRequest::factory()->approved()->create([
                'requester_id' => $traveller->id,
                'unit_id' => $centre->id,
                'b_departure_date' => '2026-07-01',
                'b_return_date' => '2026-07-02',
            ]);
        }

        $this->actingAs($dg)->get(route('travel-reports.index', [
            'financial_year' => 2026,
            'centre' => $firstCentre->id,
        ]))
            ->assertOk()
            ->assertSee('First Traveller')
            ->assertDontSee('Second Traveller');
    }

    public function test_regular_user_cannot_open_organisation_travel_reports(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('travel-reports.index'))
            ->assertForbidden();
    }

    public function test_days_are_clipped_to_each_july_to_june_financial_year(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $unit = Unit::factory()->hqStandalone()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);
        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $unit->id,
            'b_departure_date' => '2026-06-29',
            'b_return_date' => '2026-07-02',
        ]);

        $this->actingAs($dg)
            ->get(route('travel-reports.index', ['financial_year' => 2025]))
            ->assertViewHas('stats', fn (array $stats) => $stats['days'] === 2);

        $this->actingAs($dg)
            ->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertViewHas('stats', fn (array $stats) => $stats['days'] === 2);
    }
}
