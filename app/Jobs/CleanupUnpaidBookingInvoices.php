<?php

namespace App\Jobs;

use App\Models\BookingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupUnpaidBookingInvoices implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredInvoices = BookingInvoice::query()
            ->with('bookings.slots')
            ->where('status', 'unpaid')
            ->where('created_at', '<=', now()->subMinutes(15))
            ->get();

        foreach ($expiredInvoices as $invoice) {
            DB::transaction(function () use ($invoice) {
                $invoice->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                foreach ($invoice->bookings as $booking) {
                    $booking->update([
                        'status' => 'cancelled',
                        'attendance_status' => 'cancelled',
                        'cancelled_at' => now(),
                    ]);

                    foreach ($booking->slots as $slot) {
                        $slot->update([
                            'status' => 'cancelled',
                            'cancelled_at' => now(),
                        ]);

                        $slot->delete();
                    }
                }

                $booking->delete();
                Log::info("✅ Released slots, cancelled held booking, & canceled unpaid invoice #{$invoice->id}");
            });
        }
    }
}
