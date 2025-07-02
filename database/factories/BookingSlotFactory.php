<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class BookingSlotFactory extends Factory
{
    protected $model = BookingSlot::class;

    public function definition(): array
    {
        $start = Carbon::instance($this->faker->dateTimeThisMonth())->startOfHour();
        $end = (clone $start)->addHour();

        return [
            'booking_id' => Booking::factory(),
            'court_id' => Court::factory(),
            'date' => $start->toDateString(),
            'start_at' => $start,
            'end_at' => $end,
            'status' => 'held',
            'price' => $this->faker->randomFloat(2, 50000, 150000),
            'pricing_rule_id' => null,
            'cancelled_at' => null,
        ];
    }

    public function cancelled(): self
    {
        return $this->state(fn() => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function withPricingRule(): self
    {
        return $this->state(fn() => [
            'pricing_rule_id' => PricingRule::factory(),
        ]);
    }
}
