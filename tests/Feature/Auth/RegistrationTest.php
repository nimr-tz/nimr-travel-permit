<?php

namespace Tests\Feature\Auth;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $unit = Unit::create([
            'name' => 'Mwanza Research Centre',
            'code' => 'MWRC',
            'type' => 'research_centre',
            'is_active' => true,
        ]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $this->assertDatabaseHas('users', [
            'email' => 'test@nimr.or.tz',
            'unit_id' => $unit->id,
        ]);
    }

    public function test_registration_rejects_mismatched_organizational_level_and_unit(): void
    {
        $unit = Unit::create([
            'name' => 'ICT Unit',
            'code' => 'ICT',
            'type' => 'hq_standalone',
            'is_active' => true,
        ]);

        $response = $this->from('/register')->post('/register', [
            'name' => 'Test User',
            'email' => 'test@nimr.or.tz',
            'organizational_level' => 'research_centre',
            'unit_id' => $unit->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('unit_id');
        $this->assertDatabaseMissing('users', [
            'email' => 'test@nimr.or.tz',
        ]);
    }
}
