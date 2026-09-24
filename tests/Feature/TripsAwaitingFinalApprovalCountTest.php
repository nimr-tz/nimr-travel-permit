<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Services\TravelDaysService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * A trip that has ended but is still waiting on the final approver did
 * happen, so it counts toward travel days and the reports page.
 */
class TripsAwaitingFinalApprovalCountTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function trip(User $traveller, Unit $unit, User $approver, string $stage, string $from, string $to): TravelRequest
    {
        return TravelRequest::factory()->pending()->create([
            'requester_id'        => $traveller->id,
            'unit_id'             => $unit->id,
            'b_departure_date'    => $from,
            'b_return_date'       => $to,
            'current_approver_id' => $approver->id,
            'approval_chain'      => [['stage' => $stage, 'approver_id' => $approver->id]],
        ]);
    }

    public function test_ended_trips_waiting_on_the_final_approver_are_counted(): void
    {
        Carbon::setTestNow('2026-09-24 10:00:00');

        $dg = User::factory()->directorGeneral()->create();
        $unit = Unit::factory()->hqStandalone()->create();
        $traveller = User::factory()->staff()->create(['unit_id' => $unit->id]);

        $this->trip($traveller, $unit, $dg, 'final', '2026-09-09', '2026-09-11');   // counts: 3 days
        $this->trip($traveller, $unit, $dg, 'supervisor', '2026-09-01', '2026-09-02'); // earlier stage: no
        $this->trip($traveller, $unit, $dg, 'final', '2026-10-01', '2026-10-03');   // not ended: no

        $this->assertSame(3, app(TravelDaysService::class)->accumulatedDaysFor($traveller, 2026));

        $this->actingAs($dg)->get(route('travel-reports.index', ['financial_year' => 2026]))
            ->assertOk()
            ->assertViewHas('stats', fn (array $stats) => $stats['trips'] === 1 && $stats['days'] === 3);
    }
}
