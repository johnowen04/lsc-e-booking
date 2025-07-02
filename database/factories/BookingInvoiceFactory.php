<?php

namespace Database\Factories;

use App\Models\BookingInvoice;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class BookingInvoiceFactory extends Factory
{
    protected $model = BookingInvoice::class;

    public function definition(): array
    {
        $issuedAt = Carbon::parse($this->faker->dateTimeBetween('-1 day', 'now'));
        $dueAt = (clone $issuedAt)->addDays(1);

        return [
            'uuid' => Str::uuid(),
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'customer_id' => Customer::factory(),
            'customer_name' => $this->faker->name,
            'customer_phone' => $this->faker->phoneNumber,
            'paid_amount' => 0,
            'total_amount' => $this->faker->randomFloat(2, 100_000, 500_000), // adjust range as needed
            'status' => 'unpaid',
            'issued_at' => $issuedAt,
            'due_at' => $dueAt,
            'cancelled_at' => null,
            'created_by_type' => null,
            'created_by_id' => null,
            'is_walk_in' => false,
        ];
    }

    public function partiallyPaid(): static
    {
        return $this->state(function (array $attributes) {
            $paid = $attributes['total_amount'] / 2;

            return [
                'paid_amount' => $paid,
                'status' => 'partially_paid',
            ];
        });
    }

    public function fullyPaid(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'paid_amount' => $attributes['total_amount'],
                'status' => 'paid',
            ];
        });
    }

    public function cancelled(): static
    {
        return $this->state(function () {
            return [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ];
        });
    }

    public function walkIn(): static
    {
        return $this->state(fn() => ['is_walk_in' => true]);
    }
}
