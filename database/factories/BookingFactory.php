<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\BookingSlot;
use App\Models\Court;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startsAt = Carbon::parse($this->faker->dateTimeBetween('+1 day', '+2 days'))->startOfHour();
        $endsAt = (clone $startsAt)->addHour();
        $mustCheckInBefore = (clone $startsAt)->addMinutes(15);

        return [
            'uuid' => Str::uuid(),
            'booking_number' => strtoupper(Str::random(10)),
            'booking_invoice_id' => BookingInvoice::factory(),
            'customer_id' => Customer::factory(),
            'customer_name' => $this->faker->name,
            'customer_phone' => $this->faker->phoneNumber,
            'court_id' => Court::factory(),
            'date' => $startsAt->toDateString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'must_check_in_before' => $mustCheckInBefore,
            'total_price' => $this->faker->randomFloat(2, 50, 200),
            'status' => 'held',
            'attendance_status' => 'pending',
            'checked_in_at' => null,
            'cancelled_at' => null,
            'note' => null,
            'rescheduled_from_booking_id' => null,
            'created_by_type' => null,
            'created_by_id' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn() => [
            'status' => 'confirmed',
            'attendance_status' => 'pending',
        ]);
    }

    public function attended(): static
    {
        return $this->state(fn() => [
            'status' => 'confirmed',
            'attendance_status' => 'attended',
            'checked_in_at' => now(),
        ]);
    }

    public function noShow(): static
    {
        return $this->state(fn() => [
            'status' => 'confirmed',
            'attendance_status' => 'no_show',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => [
            'status' => 'cancelled',
            'attendance_status' => 'pending',
            'cancelled_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'status' => 'expired',
            'attendance_status' => 'pending',
            'expired_at' => now(),
        ]);
    }

    public function withSlots(int $count = 2): self
    {
        return $this->has(
            BookingSlot::factory()->count($count),
            'slots'
        );
    }
}
