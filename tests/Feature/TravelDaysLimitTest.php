<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\TravelDaysService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TravelDaysLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function service(): TravelDaysService
    {
        return app(TravelDaysService::class);
    }

    /** A trip is counted inclusive of both the departure and return dates. */
    public function test_days_are_counted_inclusive_of_both_dates(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $unit = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        // 1 Aug -> 5 Aug is 5 days, not 4.
        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $unit->id,
            'b_departure_date' => '2026-08-01',
            'b_return_date' => '2026-08-05',
        ]);

        $this->assertSame(5, $this->service()->accumulatedDaysFor($traveller, 2026));
    }

    /** A trip straddling 30 June splits across the two financial years. */
    public function test_trip_spanning_year_boundary_is_split(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $unit = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        // 28 Jun - 4 Jul: 3 days in FY2025 (28,29,30 Jun), 4 in FY2026 (1-4 Jul).
        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $unit->id,
            'b_departure_date' => '2026-06-28',
            'b_return_date' => '2026-07-04',
        ]);

        $this->assertSame(3, $this->service()->accumulatedDaysFor($traveller, 2025));
        $this->assertSame(4, $this->service()->accumulatedDaysFor($traveller, 2026));
    }

    /** Only approved trips accumulate — pending and draft do not reserve days. */
    public function test_only_approved_trips_count(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $unit = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $unit->id,
            'b_departure_date' => '2026-07-01',
            'b_return_date' => '2026-07-10',
        ]);

        foreach ([TravelRequest::STATUS_PENDING, TravelRequest::STATUS_DRAFT, TravelRequest::STATUS_REJECTED] as $status) {
            TravelRequest::factory()->create([
                'requester_id' => $traveller->id,
                'unit_id' => $unit->id,
                'status' => $status,
                'b_departure_date' => '2026-07-15',
                'b_return_date' => '2026-07-20',
            ]);
        }

        $this->assertSame(10, $this->service()->accumulatedDaysFor($traveller, 2026));
    }

    /** A trip that has not yet ended contributes nothing to the running total. */
    public function test_future_trips_do_not_accumulate(): void
    {
        Carbon::setTestNow('2026-08-20 10:00:00');

        $unit = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $unit->id,
            'b_departure_date' => '2026-09-01',
            'b_return_date' => '2026-09-10',
        ]);

        $this->assertSame(0, $this->service()->accumulatedDaysFor($traveller, 2026));
    }

    public function test_limit_helpers_treat_sixty_as_the_ceiling(): void
    {
        $service = $this->service();

        $this->assertSame(60, TravelDaysService::ANNUAL_LIMIT);
        $this->assertFalse($service->isOverLimit(60));
        $this->assertTrue($service->isAtLimit(60));
        $this->assertTrue($service->isOverLimit(61));
        $this->assertSame(5, $service->remaining(55));
        $this->assertSame(0, $service->remaining(70));
    }

    /** Exceeding 60 days warns but never blocks submission. */
    public function test_traveller_over_the_limit_is_warned_but_not_blocked(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        $centre = Unit::factory()->researchCentre()->create();
        $manager = User::factory()->create(['role' => 'manager', 'unit_id' => $centre->id]);
        User::factory()->create(['role' => 'centre_manager', 'unit_id' => $centre->id]);

        $traveller = User::factory()->staff()->create([
            'unit_id' => $centre->id,
            'supervisor_id' => $manager->id,
        ]);

        // 65 days already spent this financial year, all reported.
        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2026-07-01',
            'b_return_date' => '2026-09-03',
            'travel_report_document' => 'travel-reports/done.pdf',
            'travel_report_original_name' => 'done.pdf',
            'travel_report_submitted_at' => '2026-09-04 08:00:00',
        ]);

        $this->assertSame(65, $this->service()->accumulatedDaysFor($traveller, 2026));

        // The form still opens, and it warns. (Swahili is the default locale,
        // so ask for English to assert on the copy.)
        $this->actingAs($traveller)
            ->withSession(['locale' => 'en'])
            ->get(route('travel-requests.create'))
            ->assertOk()
            ->assertViewHas('travelDays', fn (?array $days) => $days['over_limit'] === true
                && $days['accumulated'] === 65
                && $days['limit'] === 60)
            ->assertSee('Above the 60-day travel guideline');
    }

    /** Someone under the limit sees the neutral usage line, not a warning. */
    public function test_traveller_under_the_limit_sees_no_warning(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        $centre = Unit::factory()->researchCentre()->create();
        $manager = User::factory()->create(['role' => 'manager', 'unit_id' => $centre->id]);
        User::factory()->create(['role' => 'centre_manager', 'unit_id' => $centre->id]);

        $traveller = User::factory()->staff()->create([
            'unit_id' => $centre->id,
            'supervisor_id' => $manager->id,
        ]);

        TravelRequest::factory()->approved()->create([
            'requester_id' => $traveller->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2026-07-01',
            'b_return_date' => '2026-07-05',
            'travel_report_document' => 'travel-reports/done.pdf',
            'travel_report_original_name' => 'done.pdf',
            'travel_report_submitted_at' => '2026-07-06 08:00:00',
        ]);

        $this->actingAs($traveller)
            ->withSession(['locale' => 'en'])
            ->get(route('travel-requests.create'))
            ->assertOk()
            ->assertViewHas('travelDays', fn (?array $days) => $days['over_limit'] === false
                && $days['accumulated'] === 5)
            ->assertDontSee('Above the 60-day travel guideline')
            ->assertSee('5 of 60 travel days used');
    }

    /** The dashboard flags people over the ceiling. */
    public function test_dashboard_flags_people_over_the_limit(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $centre = Unit::factory()->researchCentre()->create();

        $heavy = User::factory()->staff()->create(['name' => 'Heavy Traveller', 'unit_id' => $centre->id]);
        $light = User::factory()->staff()->create(['name' => 'Light Traveller', 'unit_id' => $centre->id]);

        TravelRequest::factory()->approved()->create([
            'requester_id' => $heavy->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2026-07-01',
            'b_return_date' => '2026-09-03', // 65 days
        ]);
        TravelRequest::factory()->approved()->create([
            'requester_id' => $light->id,
            'unit_id' => $centre->id,
            'b_departure_date' => '2026-07-01',
            'b_return_date' => '2026-07-04', // 4 days
        ]);

        $this->actingAs($dg)
            ->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['over_limit'] === 1)
            ->assertViewHas('people', function ($people) {
                $heavy = $people->firstWhere('name', 'Heavy Traveller');
                $light = $people->firstWhere('name', 'Light Traveller');

                return $heavy['days'] === 65
                    && $heavy['over_limit'] === true
                    && $light['days'] === 4
                    && $light['over_limit'] === false;
            });
    }

    /** HR can reach the dashboard; centre HR is scoped to their own centre. */
    public function test_hr_can_view_dashboard_and_centre_hr_is_scoped(): void
    {
        Carbon::setTestNow('2026-10-01 10:00:00');

        $hqUnit = Unit::factory()->create(['type' => 'hq_section']);
        $centreA = Unit::factory()->researchCentre()->create();
        $centreB = Unit::factory()->researchCentre()->create();

        $hqHr = User::factory()->create(['role' => 'hr', 'unit_id' => $hqUnit->id]);
        $centreHr = User::factory()->create(['role' => 'hr', 'unit_id' => $centreA->id]);

        $travellerA = User::factory()->staff()->create(['name' => 'Centre A Person', 'unit_id' => $centreA->id]);
        $travellerB = User::factory()->staff()->create(['name' => 'Centre B Person', 'unit_id' => $centreB->id]);

        foreach ([[$travellerA, $centreA], [$travellerB, $centreB]] as [$traveller, $unit]) {
            TravelRequest::factory()->approved()->create([
                'requester_id' => $traveller->id,
                'unit_id' => $unit->id,
                'b_departure_date' => '2026-07-01',
                'b_return_date' => '2026-07-05',
            ]);
        }

        // HQ HR sees the whole institute.
        $this->actingAs($hqHr)
            ->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertOk()
            ->assertSee('Centre A Person')
            ->assertSee('Centre B Person');

        // Centre HR sees only their own centre.
        $this->actingAs($centreHr)
            ->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertOk()
            ->assertSee('Centre A Person')
            ->assertDontSee('Centre B Person');
    }
}
