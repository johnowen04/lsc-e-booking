<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Court;
use App\Models\PricingRule;
use App\Models\User;

class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminUser = User::factory()->admin()->create();

        $courtA = Court::factory()->create(['name' => 'Court A']);
        $courtB = Court::factory()->create(['name' => 'Court B']);

        PricingRule::factory()->forAllCourts()->forAllDays()->create([
            'name' => 'All Day Regular',
            'description' => 'Applies to all courts and all days',
            'time_start' => '08:00:00',
            'time_end' => '17:00:00',
            'price_per_hour' => 100000,
            'type' => 'regular',
            'priority' => 1,
            'created_by' => $adminUser->id,
        ]);

        PricingRule::factory()->forAllCourts()->forAllDays()->create([
            'name' => 'All Day Peak',
            'description' => 'Peak hours for all courts and all days',
            'time_start' => '17:00:00',
            'time_end' => '22:00:00',
            'price_per_hour' => 150000,
            'type' => 'peak',
            'priority' => 2,
            'created_by' => $adminUser->id,
        ]);

        PricingRule::factory()->forAllCourts()->forAllDays()->promoForRange(
            now()->startOfWeek()->addDays(1)->toDateString(),
            now()->startOfWeek()->addDays(5)->toDateString()
        )->create([
            'name' => 'Promo Week',
            'description' => '50% discount this week only!',
            'time_start' => '08:00:00',
            'time_end' => '22:00:00',
            'price_per_hour' => 50000,
            'priority' => 3,
            'created_by' => $adminUser->id,
        ]);

        PricingRule::factory()->create([
            'name' => 'Court A Sunday Special',
            'description' => 'Court A is discounted every Sunday',
            'court_id' => $courtA->id,
            'day_of_week' => 0,
            'time_start' => '08:00:00',
            'time_end' => '22:00:00',
            'price_per_hour' => 80000,
            'type' => 'custom',
            'priority' => 4,
            'is_active' => true,
            'created_by' => $adminUser->id,
        ]);
    }
}
