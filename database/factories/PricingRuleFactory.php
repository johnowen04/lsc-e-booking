<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PricingRule>
 */
class PricingRuleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Regular Hours',
            'description' => $this->faker->optional()->sentence(),
            'court_id' => Court::factory(), // nullable in DB, override with null if needed
            'time_start' => '08:00:00',
            'time_end' => '17:00:00',
            'start_date' => null,
            'end_date' => null,
            'price_per_hour' => $this->faker->randomElement([75000, 100000, 150000]),
            'type' => $this->faker->randomElement(['regular', 'peak', 'promo', 'custom']),
            'priority' => $this->faker->numberBetween(0, 10),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function forAllCourts(): static
    {
        return $this->state(fn() => ['court_id' => null]);
    }

    public function forAllDays(): static
    {
        return $this->state(fn() => ['day_of_week' => null]);
    }

    public function promoForRange(string $start, string $end): static
    {
        return $this->state(fn() => [
            'type' => 'promo',
            'start_date' => $start,
            'end_date' => $end,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
