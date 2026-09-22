<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\ApprovalAction;
use App\Models\TravelRequest;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\TravelRequestApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AutoApproveOverdueTravelRequestsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_approves_a_request_stuck_at_the_final_approver_past_its_return_date(): void
    {
        Notification::fake();

        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id]);
        $staff = User::factory()->staff()->create(['unit_id' => $unit->id]);

        $overdue = TravelRequest::factory()->pending()->create([
            'requester_id'         => $staff->id,
            'unit_id'              => $unit->id,
            'b_departure_date'     => now()->subDays(20)->toDateString(),
            'b_return_date'        => now()->subDays(15)->toDateString(),
            'current_approver_id'  => $centreManager->id,
            'approval_chain'       => [['stage' => 'final', 'approver_id' => $centreManager->id]],
        ]);

        $this->artisan('travel-requests:auto-approve-overdue')
            ->expectsOutput('Auto-approved 1 overdue request(s).')
            ->assertSuccessful();

        $overdue->refresh();
        $this->assertSame(TravelRequest::STATUS_APPROVED, $overdue->status);
        $this->assertNull($overdue->current_approver_id);

        $action = ApprovalAction::where('travel_request_id', $overdue->id)->sole();
        $this->assertNull($action->actor_id);
        $this->assertSame('final', $action->stage);
        $this->assertSame('approved', $action->decision);

        $entry = ActivityLog::where('action', 'travel_request.auto_approved')->sole();
        $this->assertNull($entry->actor_id);
        $this->assertSame($overdue->id, $entry->subject_id);
        $this->assertSame($centreManager->id, $entry->context['previous_approver_id']);

        Notification::assertSentTo($staff, TravelRequestApprovedNotification::class);
    }

    public function test_it_leaves_requests_not_yet_due_or_not_at_the_final_stage_alone(): void
    {
        $unit = Unit::factory()->researchCentre()->create();
        $centreManager = User::factory()->centreManager()->create(['unit_id' => $unit->id]);
        $supervisor = User::factory()->supervisor()->create(['unit_id' => $unit->id]);

        $notYetDue = TravelRequest::factory()->pending()->create([
            'requester_id'        => User::factory()->staff()->create(['unit_id' => $unit->id])->id,
            'unit_id'             => $unit->id,
            'b_departure_date'    => now()->addDays(5)->toDateString(),
            'b_return_date'       => now()->addDays(10)->toDateString(),
            'current_approver_id' => $centreManager->id,
            'approval_chain'      => [['stage' => 'final', 'approver_id' => $centreManager->id]],
        ]);

        $stuckEarlier = TravelRequest::factory()->pending()->create([
            'requester_id'        => User::factory()->staff()->create(['unit_id' => $unit->id])->id,
            'unit_id'             => $unit->id,
            'b_departure_date'    => now()->subDays(20)->toDateString(),
            'b_return_date'       => now()->subDays(15)->toDateString(),
            'current_approver_id' => $supervisor->id,
            'approval_chain'      => [
                ['stage' => 'supervisor', 'approver_id' => $supervisor->id],
                ['stage' => 'final', 'approver_id' => $centreManager->id],
            ],
        ]);

        $this->artisan('travel-requests:auto-approve-overdue')
            ->expectsOutput('No overdue requests waiting on the final approver.')
            ->assertSuccessful();

        $this->assertSame(TravelRequest::STATUS_PENDING, $notYetDue->fresh()->status);
        $this->assertSame(TravelRequest::STATUS_PENDING, $stuckEarlier->fresh()->status);
    }
}
