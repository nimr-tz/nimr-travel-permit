<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityPolicyPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_system_admins_can_open_the_security_policy_page(): void
    {
        $unit = Unit::factory()->create();

        foreach (['staff', 'hr', 'director_general'] as $role) {
            $user = User::factory()->create(['role' => $role, 'unit_id' => $unit->id]);

            $this->actingAs($user)->get(route('security-policy.index'))->assertForbidden();
        }

        $admin = User::factory()->systemAdmin()->create(['unit_id' => $unit->id]);
        $this->actingAs($admin)->get(route('security-policy.index'))
            ->assertOk()
            ->assertSee(__('security_policy.title'))
            ->assertSee(__('security_policy.item.min_length.title'));
    }
}
