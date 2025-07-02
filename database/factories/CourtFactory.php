<?php

namespace Database\Factories;

use App\Models\Court;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourtFactory extends Factory
{
    protected $model = Court::class;

    public function definition(): array
    {
        return [
            'name' => 'Court ' . $this->faker->unique()->numerify('#'),
            'type' => $this->faker->randomElement(['futsal', 'badminton', 'tennis', 'basketball']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
