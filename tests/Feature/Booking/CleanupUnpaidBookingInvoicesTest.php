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

    public function test_it_cancels_unpaid_invoice_and_its_bookings_and_slots_after_15_minutes()
    {
        Carbon::setTestNow('2025-07-02 08:30:00');

        $invoice = BookingInvoice::factory()
            ->has(
                Booking::factory()
                    ->state(['status' => 'held'])
                    ->withSlots(2)
                    ->count(1),
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

        // Run the job
        (new CleanupUnpaidBookingInvoices())->handle();

        $this->assertDatabaseHas('booking_invoices', [
            'id' => $invoice->id,
            'status' => 'cancelled',
        ]);

        foreach ($invoice->fresh()->bookings as $booking) {
            $this->assertDatabaseHas('bookings', [
                'id' => $booking->id,
                'status' => 'cancelled',
                'attendance_status' => 'cancelled',
            ]);

            foreach ($booking->slots as $slot) {
                $this->assertDatabaseHas('booking_slots', [
                    'id' => $slot->id,
                    'status' => 'cancelled',
                ]);
                $this->assertSoftDeleted('booking_slots', [
                    'id' => $slot->id,
                ]);
            }

            $this->assertSoftDeleted('bookings', [
                'id' => $booking->id,
            ]);
        }
    }

    public function test_it_does_not_cancel_recent_unpaid_invoice()
    {
        Carbon::setTestNow('2025-07-02 08:30:00');

        $invoice = BookingInvoice::factory()
            ->has(
                Booking::factory()
                    ->withSlots(2),
                'bookings'
            )
            ->create([
                'status' => 'unpaid',
                'created_at' => now()->subMinutes(5),
            ]);

        // Run the job
        (new CleanupUnpaidBookingInvoices())->handle();

        $this->assertDatabaseHas('booking_invoices', [
            'id' => $invoice->id,
            'status' => 'unpaid',
        ]);
    }
}
