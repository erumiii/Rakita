<?php

namespace Database\Factories;

use App\Models\Reservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reservation>
 */
class ReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => BookFactory::new(),
            'member_id' => UserFactory::new()->member(),
            'status' => 'pending',
            'expires_at' => null,
        ];
    }

    public function offered(): static
    {
        return $this->state(fn () => ['expires_at' => now()->addDays(
            \App\Services\ReservationService::PICKUP_DAYS
        )]);
    }
}
