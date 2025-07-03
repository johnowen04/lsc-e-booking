<?php

namespace Database\Factories;

use App\Models\Court;
use App\Models\CourtScheduleSlot;
use App\Models\PricingRule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class CourtScheduleSlotFactory extends Factory
{
    protected $model = CourtScheduleSlot::class;

    public function definition(): array
    {
        $court = Court::inRandomOrder()->first() ?? Court::factory()->create();
        $date = Carbon::today()->addDays(rand(0, 30));
        $hour = $this->faker->numberBetween(8, 21);
        $startAt = Carbon::parse("{$date->toDateString()} {$hour}:00");
        $endAt = $startAt->copy()->addHour();

        $pricingRule = PricingRule::inRandomOrder()->first();

        return [
            'court_id' => $court->id,
            'date' => $date->toDateString(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'status' => $this->faker->randomElement(['available', 'held', 'confirmed', 'attended']),
            'price' => $pricingRule->price_per_hour ?? $this->faker->randomFloat(2, 25000, 100000),
            'pricing_rule_id' => $pricingRule?->id,
        ];
    }
}
