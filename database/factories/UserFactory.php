<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'               => fake()->name(),
            'email'              => fake()->unique()->safeEmail(),
            'email_verified_at'  => now(),
            'password'           => static::$password ??= Hash::make('password'),
            'remember_token'     => Str::random(10),
            'role'               => 'staff',
            'is_active'          => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(['email_verified_at' => null]);
    }

    public function staff(): static         { return $this->state(['role' => 'staff']); }
    public function head(): static          { return $this->state(['role' => 'head']); }
    public function manager(): static       { return $this->state(['role' => 'manager']); }
    public function director(): static      { return $this->state(['role' => 'director']); }
    public function centreManager(): static { return $this->state(['role' => 'centre_manager']); }
    public function directorGeneral(): static { return $this->state(['role' => 'director_general']); }
    public function hr(): static            { return $this->state(['role' => 'hr']); }
    public function systemAdmin(): static   { return $this->state(['role' => 'system_admin']); }
    public function inactive(): static      { return $this->state(['is_active' => false]); }
}
