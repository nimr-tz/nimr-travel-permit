<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use App\Services\ApprovalChainService;
use App\Services\SupervisorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentreDirectReportingTest extends TestCase
{
    use RefreshDatabase;

    private Unit $centre;
    private User $centreManager;
    private User $supervisor;

    protected function setUp(): void
    {
        parent::setUp();

        User::factory()->create(['role' => 'director_general', 'unit_id' => null]);
        $this->centre = Unit::factory()->create(['type' => 'research_centre']);
        $this->centreManager = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'centre_manager']);
        $this->supervisor = User::factory()->create(['unit_id' => $this->centre->id, 'role' => 'supervisor', 'supervisor_id' => $this->centreManager->id]);
    }

    private function member(string $role, ?int $supervisorId = null): User
    {
        return User::factory()->create([
            'unit_id' => $this->centre->id,
            'role' => $role,
            'supervisor_id' => $supervisorId,
        ]);
    }

    public function test_every_active_colleague_in_the_centre_is_offered(): void
    {
        $colleague = $this->member('staff');
        $centreHr = $this->member('hr');
        $centreAdmin = $this->member('system_admin');

        $outsider = User::factory()->create([
            'unit_id' => Unit::factory()->create(['type' => 'research_centre'])->id,
            'role' => 'staff',
        ]);
        $inactive = User::factory()->create([
            'unit_id' => $this->centre->id, 'role' => 'staff', 'is_active' => false,
        ]);

        $service = app(SupervisorService::class);

        foreach (['staff', 'hr', 'system_admin'] as $role) {
            $member = $this->member($role);
            $ids = $service->candidatesFor($member)->pluck('id')->all();

            foreach ([$this->supervisor, $this->centreManager, $colleague, $centreHr, $centreAdmin] as $expected) {
                $this->assertContains($expected->id, $ids, "$role was not offered {$expected->role}");
            }

            $this->assertNotContains($outsider->id, $ids, 'someone from another centre was offered');
            $this->assertNotContains($inactive->id, $ids, 'a deactivated colleague was offered');
            $this->assertNotContains($member->id, $ids, 'the member was offered themselves');
        }
    }

    /** Nothing is assigned for them — the choice is theirs to make. */
    public function test_the_centre_manager_is_never_assigned_automatically(): void
    {
        $service = app(SupervisorService::class);

        foreach (['staff', 'hr', 'system_admin'] as $role) {
            $member = $this->member($role);

            $this->assertNull($service->fixedSupervisorFor($member), "$role had a supervisor pinned for them");
            $this->assertNull($service->applyFixedSupervisor($member), "$role had a supervisor written automatically");
            $this->assertNull($member->fresh()->supervisor_id, "$role ended up with a supervisor they never chose");
        }
    }

    public function test_choosing_the_centre_manager_gives_a_single_step_chain(): void
    {
        $staff = $this->member('staff', $this->centreManager->id);

        $chain = app(ApprovalChainService::class)->buildChain($staff);

        $this->assertCount(1, $chain, 'the Centre Manager would have approved the same request twice');
        $this->assertSame('final', $chain[0]['stage']);
        $this->assertSame($this->centreManager->id, $chain[0]['approver_id']);
    }

    public function test_choosing_a_supervisor_still_gives_the_two_step_chain(): void
    {
        $staff = $this->member('staff', $this->supervisor->id);

        $chain = app(ApprovalChainService::class)->buildChain($staff);

        $this->assertCount(2, $chain);
        $this->assertSame($this->supervisor->id, $chain[0]['approver_id']);
        $this->assertSame($this->centreManager->id, $chain[1]['approver_id']);
    }

    public function test_a_centre_with_no_supervisor_no_longer_strands_its_staff(): void
    {
        $this->supervisor->forceFill(['is_active' => false])->save();
        $staff = $this->member('staff');

        $ids = app(SupervisorService::class)->candidatesFor($staff)->pluck('id')->all();

        $this->assertSame([$this->centreManager->id], $ids,
            'staff in a centre with no active Supervisor had nobody to select');
    }

    /**
     * Whoever is chosen approves first and it goes to the Centre Manager after
     * them — whatever role they hold — unless the Centre Manager is the choice.
     */
    public function test_an_ordinary_colleague_approves_first_then_the_centre_manager(): void
    {
        $colleague = $this->member('staff');
        $staff = $this->member('staff', $colleague->id);

        $chain = app(ApprovalChainService::class)->buildChain($staff);

        $this->assertCount(2, $chain);
        $this->assertSame($colleague->id, $chain[0]['approver_id']);
        $this->assertSame('supervisor', $chain[0]['stage']);
        $this->assertSame($this->centreManager->id, $chain[1]['approver_id']);
        $this->assertSame('final', $chain[1]['stage']);
    }

    public function test_a_centre_hr_officer_chosen_as_supervisor_routes_the_same_way(): void
    {
        $centreHr = $this->member('hr');
        $staff = $this->member('staff', $centreHr->id);

        $chain = app(ApprovalChainService::class)->buildChain($staff);

        $this->assertSame([$centreHr->id, $this->centreManager->id], array_column($chain, 'approver_id'));
    }

    public function test_a_member_may_pick_the_centre_manager_on_their_own_dashboard(): void
    {
        $staff = $this->member('staff');

        $this->actingAs($staff)
            ->patch(route('dashboard.supervisor.update'), ['supervisor_id' => $this->centreManager->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($this->centreManager->id, $staff->fresh()->supervisor_id);
    }
}
