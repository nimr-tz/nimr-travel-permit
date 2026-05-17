<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_root_route_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_open_the_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_the_users_supervisor(): void
    {
        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $supervisor = User::factory()->create([
            'name' => 'Jane Supervisor',
            'unit_id' => $unit->id,
            'role' => 'manager',
            'job_title' => 'Research Manager',
        ]);

        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('dashboard.my_supervisor'));
        $response->assertSee('Jane Supervisor');
        $response->assertSee('Research Manager');
    }

    public function test_user_can_update_their_supervisor_from_dashboard(): void
    {
        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $supervisor = User::factory()->create([
            'name' => 'Jane Supervisor',
            'unit_id' => $unit->id,
            'role' => 'manager',
        ]);

        $user = User::factory()->create([
            'unit_id' => $unit->id,
            'role' => 'staff',
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($user)->patch(route('dashboard.supervisor.update'), [
            'supervisor_id' => $supervisor->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status', __('dashboard.supervisor_updated'));
        $this->assertSame($supervisor->id, $user->refresh()->supervisor_id);
    }

    public function test_user_cannot_choose_a_supervisor_from_another_unit(): void
    {
        $userUnit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $otherUnit = Unit::create([
            'name' => 'Tanga Research Centre',
            'code' => 'TGRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $outsideSupervisor = User::factory()->create([
            'unit_id' => $otherUnit->id,
            'role' => 'manager',
        ]);

        $user = User::factory()->create([
            'unit_id' => $userUnit->id,
            'role' => 'staff',
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($user)->from(route('dashboard'))->patch(route('dashboard.supervisor.update'), [
            'supervisor_id' => $outsideSupervisor->id,
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHasErrors('supervisor_id');
        $this->assertNull($user->refresh()->supervisor_id);
    }
}
