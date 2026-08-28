<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_admin_can_assign_supervisor_when_creating_user(): void
    {
        Notification::fake();

        $admin = User::factory()->systemAdmin()->create();
        $unit = Unit::factory()->researchCentre()->create();
        $supervisor = User::factory()->manager()->create([
            'name' => 'Jane Supervisor',
            'unit_id' => $unit->id,
        ]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Staff Member',
            'email' => 'new.staff@example.test',
            'unit_id' => $unit->id,
            'phone' => '+255700000001',
            'job_title' => 'Research Officer',
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'new.staff@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($supervisor->id, $created->supervisor_id);
    }

    public function test_admin_can_assign_supervisor_when_updating_user(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $unit = Unit::factory()->researchCentre()->create();
        $supervisor = User::factory()->manager()->create(['unit_id' => $unit->id]);
        $staff = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('users.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'unit_id' => $unit->id,
            'phone' => $staff->phone,
            'job_title' => $staff->job_title,
            'role' => 'staff',
            'supervisor_id' => $supervisor->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame($supervisor->id, $staff->refresh()->supervisor_id);
    }

    public function test_centre_admin_cannot_assign_supervisor_from_another_unit(): void
    {
        $centre = Unit::factory()->researchCentre()->create();
        $otherCentre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);
        $outsideSupervisor = User::factory()->manager()->create(['unit_id' => $otherCentre->id]);
        $staff = User::factory()->staff()->create([
            'unit_id' => $centre->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($admin)->from(route('users.edit', $staff))->patch(route('users.update', $staff), [
            'name' => $staff->name,
            'email' => $staff->email,
            'unit_id' => $centre->id,
            'phone' => $staff->phone,
            'job_title' => $staff->job_title,
            'role' => 'staff',
            'supervisor_id' => $outsideSupervisor->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.edit', $staff));
        $response->assertSessionHasErrors('supervisor_id');
        $this->assertNull($staff->refresh()->supervisor_id);
    }

    public function test_hq_standalone_manager_is_automatically_assigned_dg_as_supervisor(): void
    {
        Notification::fake();

        $admin = User::factory()->systemAdmin()->create();
        $dg = User::factory()->directorGeneral()->create();
        $unit = Unit::factory()->hqStandalone()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Direct Manager',
            'email' => 'direct.manager@example.test',
            'unit_id' => $unit->id,
            'role' => 'manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'direct.manager@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($dg->id, $created->supervisor_id);
    }

    public function test_hq_directorate_director_is_automatically_assigned_dg_as_supervisor(): void
    {
        Notification::fake();

        $admin = User::factory()->systemAdmin()->create();
        $dg = User::factory()->directorGeneral()->create();
        $unit = Unit::factory()->hqDirectorate()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Director One',
            'email' => 'director.one@example.test',
            'unit_id' => $unit->id,
            'role' => 'director',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'director.one@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($dg->id, $created->supervisor_id);
    }

    public function test_hq_section_head_is_automatically_assigned_directorate_director_as_supervisor(): void
    {
        Notification::fake();

        $admin      = User::factory()->systemAdmin()->create();
        $directorate = Unit::factory()->hqDirectorate()->create();
        $director   = User::factory()->director()->create(['unit_id' => $directorate->id]);
        $section    = Unit::factory()->hqSection()->create(['parent_id' => $directorate->id]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Head One',
            'email' => 'head.one@example.test',
            'unit_id' => $section->id,
            'role' => 'head',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'head.one@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($director->id, $created->supervisor_id);
    }

    public function test_hq_section_manager_is_automatically_assigned_directorate_director_as_supervisor(): void
    {
        Notification::fake();

        $admin      = User::factory()->systemAdmin()->create();
        $directorate = Unit::factory()->hqDirectorate()->create();
        $director   = User::factory()->director()->create(['unit_id' => $directorate->id]);
        $section    = Unit::factory()->hqSection()->create(['parent_id' => $directorate->id]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Manager One',
            'email' => 'manager.one@example.test',
            'unit_id' => $section->id,
            'role' => 'manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'manager.one@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($director->id, $created->supervisor_id);
    }

    public function test_hq_section_head_creation_fails_when_directorate_has_no_active_director(): void
    {
        $admin   = User::factory()->systemAdmin()->create();
        $directorate = Unit::factory()->hqDirectorate()->create();
        $section = Unit::factory()->hqSection()->create(['parent_id' => $directorate->id]);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Head Two',
            'email' => 'head.two@example.test',
            'unit_id' => $section->id,
            'role' => 'head',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('supervisor_id');
        $this->assertNull(User::where('email', 'head.two@example.test')->first());
    }

    public function test_updating_user_to_hq_standalone_manager_automatically_assigns_dg_as_supervisor(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $dg = User::factory()->directorGeneral()->create();
        $unit = Unit::factory()->hqStandalone()->create();
        $user = User::factory()->staff()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'unit_id' => $unit->id,
            'phone' => $user->phone,
            'job_title' => $user->job_title,
            'role' => 'manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();
        $this->assertSame($dg->id, $user->refresh()->supervisor_id);
    }

    public function test_edit_form_exposes_dg_for_stale_direct_manager_without_saved_supervisor(): void
    {
        $admin = User::factory()->systemAdmin()->create();
        $dg = User::factory()->directorGeneral()->create(['name' => 'Director General User']);
        $unit = Unit::factory()->hqStandalone()->create();
        $manager = User::factory()->manager()->create([
            'unit_id' => $unit->id,
            'supervisor_id' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('users.edit', $manager));

        $response->assertOk();
        $response->assertSee('Director General User');
        $response->assertSee(__('users.field_supervisor_auto'));
    }

    public function test_centre_manager_is_automatically_assigned_dg_as_supervisor(): void
    {
        Notification::fake();

        $centre = Unit::factory()->researchCentre()->create();
        $admin = User::factory()->systemAdmin()->create(['unit_id' => $centre->id]);
        $dg = User::factory()->directorGeneral()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Centre Manager',
            'email' => 'centre.manager@example.test',
            'unit_id' => $centre->id,
            'role' => 'centre_manager',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHasNoErrors();

        $created = User::where('email', 'centre.manager@example.test')->first();

        $this->assertNotNull($created);
        $this->assertSame($dg->id, $created->supervisor_id);
    }
}
