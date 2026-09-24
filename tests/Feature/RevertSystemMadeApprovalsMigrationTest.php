<?php

namespace Tests\Feature;

use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevertSystemMadeApprovalsMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function runMigration(): void
    {
        (require database_path('migrations/2026_09_24_000001_revert_system_made_approvals.php'))->up();
    }

    private function leftover(User $dg, User $staff, Unit $unit): TravelRequest
    {
        $request = TravelRequest::factory()->approved()->create([
            'requester_id'   => $staff->id,
            'unit_id'        => $unit->id,
            'approval_chain' => [['stage' => 'final', 'approver_id' => $dg->id]],
        ]);

        ApprovalAction::create([
            'travel_request_id' => $request->id,
            'actor_id'          => null,
            'stage'             => 'final',
            'decision'          => 'approved',
            'comment'           => 'Auto-approved: return date passed without a decision from the final approver.',
            'acted_at'          => now(),
        ]);

        return $request;
    }

    public function test_a_system_made_approval_goes_back_to_waiting_on_the_final_approver(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $dg = User::factory()->directorGeneral()->create(['unit_id' => null]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->leftover($dg, $staff, $unit);

        $this->runMigration();

        $request->refresh();
        $this->assertSame(TravelRequest::STATUS_PENDING, $request->status);
        $this->assertSame($dg->id, $request->current_approver_id);
        $this->assertSame(0, $request->approvalActions()->count());
    }

    public function test_a_real_decision_by_the_final_approver_is_kept(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $dg = User::factory()->directorGeneral()->create(['unit_id' => null]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = $this->leftover($dg, $staff, $unit);

        ApprovalAction::create([
            'travel_request_id' => $request->id,
            'actor_id'          => $dg->id,
            'stage'             => 'final',
            'decision'          => 'approved',
            'acted_at'          => now()->addHour(),
        ]);

        $this->runMigration();

        $request->refresh();
        $this->assertSame(TravelRequest::STATUS_APPROVED, $request->status);
        $this->assertSame(1, $request->approvalActions()->count());
        $this->assertSame($dg->id, $request->approvalActions()->first()->actor_id);
    }

    public function test_genuine_approvals_are_untouched(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $dg = User::factory()->directorGeneral()->create(['unit_id' => null]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);
        $request = TravelRequest::factory()->approved()->create([
            'requester_id' => $staff->id,
            'unit_id'      => $unit->id,
            'approval_chain' => [['stage' => 'final', 'approver_id' => $dg->id]],
        ]);
        ApprovalAction::create([
            'travel_request_id' => $request->id,
            'actor_id'          => $dg->id,
            'stage'             => 'final',
            'decision'          => 'approved',
            'acted_at'          => now(),
        ]);

        $this->runMigration();

        $this->assertSame(TravelRequest::STATUS_APPROVED, $request->fresh()->status);
        $this->assertSame(1, $request->approvalActions()->count());
    }
}
