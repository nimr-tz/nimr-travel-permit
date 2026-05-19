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

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'New Centre User',
                'email' => 'new.centre.user@example.com',
                'unit_id' => $centre->id,
                'role' => 'manager',
                'is_active' => '1',
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'new.centre.user@example.com',
            'unit_id' => $centre->id,
            'role' => 'manager',
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
}
