<?php

namespace Database\Factories;

use App\Models\TravelRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TravelRequest> */
class TravelRequestFactory extends Factory
{
    public function definition(): array
    {
        static $seq = 0;
        $seq++;

        return [
            'request_number'   => 'NIMR-ITP-' . now()->year . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'status'           => TravelRequest::STATUS_DRAFT,
            'b_applicant_name' => fake()->name(),
            'b_destination'    => fake()->city() . ', Tanzania',
            'b_departure_date' => now()->addDays(7)->toDateString(),
            'b_return_date'    => now()->addDays(10)->toDateString(),
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TravelRequest::STATUS_PENDING, 'submitted_at' => now()]);
    }

    public function approved(): static
    {
        return $this->state(['status' => TravelRequest::STATUS_APPROVED, 'current_approver_id' => null]);
    }

    public function returned(): static
    {
        return $this->state(['status' => TravelRequest::STATUS_RETURNED, 'current_approver_id' => null]);
    }
}
