<?php

namespace App\Jobs;

use App\Models\BookingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class CleanupUnpaidBookingInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('🧹 Starting cleanup of unpaid booking invoices...');

        BookingInvoice::query()
            ->with('bookings.slots.courtScheduleSlot')
            ->where('status', 'unpaid')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->chunkById(50, function ($invoices) {
                foreach ($invoices as $invoice) {
                    DB::transaction(function () use ($invoice) {
                        $now = now();

                        $invoice->update([
                            'status' => 'expired',
                            'expired_at' => $now,
                        ]);

                        foreach ($invoice->bookings as $booking) {
                            $booking->update([
                                'status' => 'expired',
                                'attendance_status' => 'pending',
                                'expired_at' => $now,
                            ]);

                            foreach ($booking->slots as $slot) {
                                if ($slot->court_schedule_slot_id && $slot->courtScheduleSlot) {
                                    $slot->courtScheduleSlot->update([
                                        'status' => 'available',
                                    ]);
                                }

                                $slot->update([
                                    'status' => 'expired',
                                    'expired_at' => $now,
                                ]);
                            }
                        }

                        Log::info("🕓 Invoice #{$invoice->id} expired. Bookings and slots marked as expired.");
                    });
                }
            });

        Log::info('✅ Finished cleanup of unpaid booking invoices.');
    }
}
