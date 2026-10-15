<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_cannot_access_user_management(): void
    {
        $hr = User::factory()->hr()->create();

        $this->actingAs($hr)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_global_system_admin_can_view_all_users(): void
    {
        $hq = Unit::factory()->hqStandalone()->create();
        $centre = Unit::factory()->researchCentre()->create();

        $admin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);
        $hqUser = User::factory()->staff()->create(['unit_id' => $hq->id, 'name' => 'HQ Staff']);
        $centreUser = User::factory()->staff()->create(['unit_id' => $centre->id, 'name' => 'Centre Staff']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($hqUser->name)
            ->assertSee($centreUser->name);
    }

    public function test_centre_system_admin_only_sees_their_centre_users(): void
    {
        $centreA = Unit::factory()->researchCentre()->create();
        $centreB = Unit::factory()->researchCentre()->create();

        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centreA->id]);
        $ownUser = User::factory()->staff()->create(['unit_id' => $centreA->id, 'name' => 'Own Centre Staff']);
        $otherUser = User::factory()->staff()->create(['unit_id' => $centreB->id, 'name' => 'Other Centre Staff']);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee($ownUser->name)
            ->assertDontSee($otherUser->name);
    }

    public function test_centre_system_admin_cannot_edit_another_centre_user(): void
    {
        $centreA = Unit::factory()->researchCentre()->create();
        $centreB = Unit::factory()->researchCentre()->create();

        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centreA->id]);
        $otherUser = User::factory()->staff()->create(['unit_id' => $centreB->id]);

        $this->actingAs($admin)
            ->get(route('users.edit', $otherUser))
            ->assertForbidden();
    }

    public function test_centre_system_admin_can_create_non_admin_user_in_their_centre(): void
    {
        Notification::fake();

        $centre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);
        User::factory()->centreManager()->create(['unit_id' => $centre->id]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Centre User',
                'email' => 'new.centre.user@example.com',
                'unit_id' => $centre->id,
                'role' => 'supervisor',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new.centre.user@example.com',
            'unit_id' => $centre->id,
            'role' => 'supervisor',
        ]);
    }

    public function test_centre_system_admin_cannot_create_system_admins(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Centre Admin Escalation',
                'email' => 'centre.escalation@example.com',
                'unit_id' => $centre->id,
                'role' => 'system_admin',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_centre_system_admin_cannot_grant_administrator_access(): void
    {
        Notification::fake();

        $centre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);
        User::factory()->centreManager()->create(['unit_id' => $centre->id]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Centre Admin Escalation',
                'email' => 'centre.escalation@example.com',
                'unit_id' => $centre->id,
                'role' => 'supervisor',
                'is_system_admin' => '1',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse(User::where('email', 'centre.escalation@example.com')->firstOrFail()->isSystemAdmin());
    }

    public function test_system_admin_can_also_hold_a_post(): void
    {
        Notification::fake();

        $hq = Unit::factory()->hqStandalone()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);
        User::factory()->directorGeneral()->create(['unit_id' => null]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'ICT Manager',
                'email' => 'ict.manager@example.com',
                'unit_id' => $hq->id,
                'role' => 'manager',
                'is_system_admin' => '1',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $manager = User::where('email', 'ict.manager@example.com')->firstOrFail();

        $this->assertSame('manager', $manager->role);
        $this->assertTrue($manager->isSystemAdmin());
        $this->assertTrue($manager->isApprover(), 'administrator access took them out of the approval chain');
        $this->assertSame(['manager', 'system_admin'], $manager->roleNames());

        $manager->markEmailAsVerified();
        $this->actingAs($manager)->get(route('users.index'))->assertOk();
    }

    public function test_global_system_admin_can_add_and_remove_administrator_access(): void
    {
        $hq = Unit::factory()->hqStandalone()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);
        $dg = User::factory()->directorGeneral()->create(['unit_id' => null]);
        $manager = User::factory()->manager()->create(['unit_id' => $hq->id, 'supervisor_id' => $dg->id]);

        $payload = fn (bool $isAdmin) => [
            'name' => $manager->name,
            'email' => $manager->email,
            'unit_id' => $hq->id,
            'role' => 'manager',
            'is_system_admin' => $isAdmin ? '1' : '0',
            'is_active' => '1',
        ];

        $this->actingAs($admin)->patch(route('users.update', $manager), $payload(true))->assertSessionHasNoErrors();
        $this->assertTrue($manager->fresh()->isSystemAdmin());
        $this->assertSame('manager', $manager->fresh()->role);

        $this->actingAs($admin)->patch(route('users.update', $manager), $payload(false))->assertSessionHasNoErrors();
        $this->assertFalse($manager->fresh()->isSystemAdmin());
    }

    public function test_system_admin_cannot_revoke_their_own_administrator_access(): void
    {
        $hq = Unit::factory()->hqStandalone()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $hq->id]);

        $this->actingAs($admin)
            ->patch(route('users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'unit_id' => $hq->id,
                'role' => 'staff',
                'is_system_admin' => '0',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('is_system_admin');

        $this->assertTrue($admin->fresh()->isSystemAdmin());
    }

    public function test_global_system_admin_can_export_users_as_csv(): void
    {
        $unit = Unit::factory()->hqStandalone()->create(['name' => 'Head Office']);
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $supervisor = User::factory()->manager()->create([
            'unit_id' => $unit->id,
            'name' => 'Export Supervisor',
        ]);
        $user = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => $supervisor->id,
            'name' => 'Export Person',
            'email' => 'export.person@example.com',
            'phone' => '0712345678',
            'job_title' => 'Researcher',
        ]);

        $response = $this->actingAs($admin)->get(route('users.export'));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Name,Email,Phone,"Job title",Unit,Role,"System administrator",Supervisor,Status,"Date added"', $csv);
        $this->assertStringContainsString($user->email, $csv);
        $this->assertStringContainsString('Export Supervisor', $csv);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'download.user_export',
            'actor_id' => $admin->id,
        ]);
    }

    public function test_centre_system_admin_export_is_scoped_to_their_centre(): void
    {
        $centreA = Unit::factory()->researchCentre()->create();
        $centreB = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centreA->id]);
        $ownUser = User::factory()->staff()->create([
            'unit_id' => $centreA->id,
            'email' => 'own-centre@example.com',
        ]);
        $otherUser = User::factory()->staff()->create([
            'unit_id' => $centreB->id,
            'email' => 'other-centre@example.com',
        ]);

        $csv = $this->actingAs($admin)->get(route('users.export'))->streamedContent();

        $this->assertStringContainsString($ownUser->email, $csv);
        $this->assertStringNotContainsString($otherUser->email, $csv);
        $this->assertStringNotContainsString($admin->email, $csv);
    }

    public function test_user_export_respects_search_and_neutralises_formulas(): void
    {
        $unit = Unit::factory()->hqStandalone()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'name' => '=Dangerous Name',
            'email' => 'matching@example.com',
        ]);
        User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'email' => 'excluded@example.com',
        ]);

        $csv = $this->actingAs($admin)
            ->get(route('users.export', ['q' => 'matching@example.com']))
            ->streamedContent();

        $this->assertStringContainsString("'=Dangerous Name", $csv);
        $this->assertStringContainsString('matching@example.com', $csv);
        $this->assertStringNotContainsString('excluded@example.com', $csv);
    }

    public function test_non_admin_cannot_export_users(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)
            ->get(route('users.export'))
            ->assertForbidden();
    }
}
