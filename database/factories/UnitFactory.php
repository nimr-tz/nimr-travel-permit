<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Unit> */
class UnitFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'      => fake()->company(),
            'code'      => strtoupper(fake()->unique()->lexify('????')),
            'type'      => 'hq_standalone',
            'parent_id' => null,
            'is_active' => true,
        ];
    }

    public function researchCentre(): static
    {
        return $this->state(['type' => 'research_centre']);
    }

    public function hqDirectorate(): static
    {
        return $this->state(['type' => 'hq_directorate']);
    }

    public function hqSection(): static
    {
        return $this->state(['type' => 'hq_section']);
    }

    public function hqStandalone(): static
    {
        return $this->state(['type' => 'hq_standalone']);
    }
}
