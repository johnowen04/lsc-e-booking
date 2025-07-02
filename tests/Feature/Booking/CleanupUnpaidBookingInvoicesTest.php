<?php

namespace Tests\Feature\Booking;

use App\Jobs\CleanupUnpaidBookingInvoices;
use App\Models\Booking;
use App\Models\BookingInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CleanupUnpaidBookingInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_invoice_and_related_data_are_expired_after_15_minutes(): void
    {
        Carbon::setTestNow('2025-07-02 08:30:00');

        $invoice = BookingInvoice::factory()
            ->has(
                Booking::factory()
                    ->state(['status' => 'held'])
                    ->withSlots(2),
                'bookings'
            )
            ->create([
                'status' => 'unpaid',
                'created_at' => now()->subMinutes(20),
            ]);

        $this->assertDatabaseHas('booking_invoices', [
            'id' => $invoice->id,
            'status' => 'unpaid',
        ]);

        (new CleanupUnpaidBookingInvoices())->handle();

        $this->assertDatabaseHas('booking_invoices', [
            'id' => $invoice->id,
            'status' => 'expired',
            'expired_at' => now(),
        ]);

        $invoice->fresh()->bookings->each(function (Booking $booking) {
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => 'expired',
                'attendance_status' => 'pending',
                'expired_at' => now(),
            ]);

            $this->assertSoftDeleted('bookings', ['id' => $booking->id]);

            $booking->slots->each(function ($slot) {
                $this->assertDatabaseHas('booking_slots', [
                    'id' => $slot->id,
                    'status' => 'expired',
                    'expired_at' => now(),
                ]);

                $this->assertSoftDeleted('booking_slots', ['id' => $slot->id]);
            });
        });
    }

    public function test_recent_unpaid_invoice_is_not_expired(): void
    {
        Carbon::setTestNow('2025-07-02 08:30:00');

        $invoice = BookingInvoice::factory()
            ->has(Booking::factory()->withSlots(2), 'bookings')
            ->create([
                'status' => 'unpaid',
                'created_at' => now()->subMinutes(5),
            ]);

        (new CleanupUnpaidBookingInvoices())->handle();

        $this->assertDatabaseHas('booking_invoices', [
            'id' => $invoice->id,
            'status' => 'unpaid',
        ]);
    }
}
