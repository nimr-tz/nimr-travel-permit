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

    public function test_per_person_report_columns_add_up_to_their_trips(): void
    {
        // Production, Sept 2026: people with one approved trip that had not
        // ended yet read as "1 trip, 0 days, 0 submitted, 0 missing" — the
        // not-yet-due trip was counted nowhere.
        Carbon::setTestNow('2026-09-16 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $centre = Unit::factory()->researchCentre()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $centre->id]);

        $trips = [
            // Ended, report in: 5 days.
            ['2026-08-01', '2026-08-05', '2026-08-06 09:00:00'],
            // Ended, no report: 2 days, missing.
            ['2026-09-01', '2026-09-02', null],
            // Under way (departed, returns later): not due, 3 planned days.
            ['2026-09-15', '2026-09-17', null],
            // Not yet started: not due, 4 planned days.
            ['2026-10-01', '2026-10-04', null],
        ];

        foreach ($trips as [$departure, $return, $reportedAt]) {
            TravelRequest::factory()->approved()->create([
                'requester_id' => $traveller->id,
                'unit_id' => $centre->id,
                'b_departure_date' => $departure,
                'b_return_date' => $return,
                'travel_report_submitted_at' => $reportedAt,
                'travel_report_document' => $reportedAt ? 'travel-reports/r.pdf' : null,
            ]);
        }

        $this->actingAs($dg)->withSession(['locale' => 'en'])->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertOk()
            ->assertViewHas('people', function ($people) {
                $person = $people->sole();

                return $person['trips'] === 4
                    && $person['days'] === 7
                    && $person['upcoming_days'] === 7
                    && $person['submitted'] === 1
                    && $person['missing'] === 1
                    && $person['not_due'] === 2
                    && $person['submitted'] + $person['missing'] + $person['not_due'] === $person['trips'];
            })
            ->assertSee('+7 upcoming');
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

    private function adminWithTrips(array $trips): User
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        foreach ($trips as [$departure, $return]) {
            TravelRequest::factory()->approved()->create([
                'requester_id' => $traveller->id,
                'unit_id' => $unit->id,
                'b_departure_date' => $departure,
                'b_return_date' => $return,
            ]);
        }

        return $admin;
    }

    public function test_admin_can_narrow_reports_to_a_date_range(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        $admin = $this->adminWithTrips([
            ['2026-01-10', '2026-01-12'], // before the range
            ['2026-01-30', '2026-02-02'], // straddles the start: 2 days inside
            ['2026-03-01', '2026-03-05'], // inside: 5 days
            ['2026-05-01', '2026-05-03'], // after the range
        ]);

        $this->actingAs($admin)->withSession(['locale' => 'en'])
            ->get(route('travel-reports.index', ['date_from' => '2026-02-01', 'date_to' => '2026-04-30']))
            ->assertOk()
            ->assertSee('01 Feb 2026 to 30 Apr 2026')
            ->assertViewHas('stats', fn (array $stats) => $stats['trips'] === 2 && $stats['days'] === 7)
            ->assertViewHas('requests', fn ($requests) => $requests->total() === 2);
    }

    public function test_either_end_of_the_range_can_be_left_open(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        $admin = $this->adminWithTrips([
            ['2025-03-01', '2025-03-02'],
            ['2026-03-01', '2026-03-03'],
        ]);

        $this->actingAs($admin)
            ->get(route('travel-reports.index', ['date_from' => '2026-01-01']))
            ->assertViewHas('stats', fn (array $stats) => $stats['trips'] === 1 && $stats['days'] === 3);

        $this->actingAs($admin)
            ->get(route('travel-reports.index', ['date_to' => '2025-12-31']))
            ->assertViewHas('stats', fn (array $stats) => $stats['trips'] === 1 && $stats['days'] === 2);
    }

    public function test_day_limit_is_not_judged_across_more_than_one_financial_year(): void
    {
        Carbon::setTestNow('2026-07-31 10:00:00');

        // 50 days in 2025/26 and 30 in 2026/27: 80 in total, but nobody
        // has broken the 60-day-per-year limit.
        $admin = $this->adminWithTrips([
            ['2025-08-01', '2025-09-09'],
            ['2026-07-01', '2026-07-30'],
            ['2026-06-01', '2026-06-10'],
        ]);

        $this->actingAs($admin)
            ->get(route('travel-reports.index', ['date_from' => '2025-07-01', 'date_to' => '2026-07-31']))
            ->assertViewHas('stats', fn (array $stats) => $stats['days'] === 80 && $stats['over_limit'] === null)
            ->assertViewHas('people', fn ($people) => $people->sole()['over_limit'] === false);
    }

    public function test_a_range_that_ends_before_it_starts_is_rejected(): void
    {
        $admin = $this->adminWithTrips([]);

        $this->actingAs($admin)
            ->get(route('travel-reports.index', ['date_from' => '2026-05-01', 'date_to' => '2026-04-01']))
            ->assertSessionHasErrors('date_to');
    }
}
