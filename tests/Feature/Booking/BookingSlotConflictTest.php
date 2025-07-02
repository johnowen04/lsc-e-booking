<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingSlotConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_overlapping_booking_is_rejected()
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::factory()->create();
        $court = Court::first();
        $date = '2025-07-05';

        $flow = new CreateBookingFlow(
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PaymentService::class),
            app(MidtransService::class),
        );

        // First booking: 08:00–10:00 (creates 2 slots)
        $flow->execute(
            formData: [
                'customer_name' => 'Alice',
                'customer_phone' => '0811111111',
                'is_paid_in_full' => true,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            groupedSlots: [[
                'court_id' => $court->id,
                'date' => $date,
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
            ]],
            customer: null,
            options: [
                'created_by_type' => $user::class,
                'created_by_id' => $user->id,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        // Second booking: 09:00–11:00 — overlaps with first
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->expectExceptionMessage('UNIQUE constraint failed: booking_slots.court_id, booking_slots.start_at, booking_slots.end_at');

        $flow->execute(
            formData: [
                'customer_name' => 'Bob',
                'customer_phone' => '0822222222',
                'is_paid_in_full' => true,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            groupedSlots: [[
                'court_id' => $court->id,
                'date' => $date,
                'start_time' => '09:00:00',
                'end_time' => '11:00:00',
            ]],
            customer: null,
            options: [
                'created_by_type' => $user::class,
                'created_by_id' => $user->id,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );
    }
}
