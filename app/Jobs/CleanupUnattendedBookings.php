<?php

namespace App\Jobs;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupUnattendedBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('🎯 Starting unattended booking cleanup...');

        Booking::query()
            ->with('slots')
            ->where('status', 'confirmed')
            ->where('attendance_status', 'pending')
            ->where('must_check_in_before', '<', now())
            ->chunkById(50, function ($bookings) {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking) {
                        $booking->update([
                            'attendance_status' => 'no_show',
                            'status' => 'no_show',
                        ]);

                        foreach ($booking->slots as $slot) {
                            $slot->update(['status' => 'no_show']);
                            $slot->delete();
                        }

                        $booking->delete();

                        Log::info("🚫 Booking #{$booking->id} marked as no_show and released.");
                    });
                }
            });

        Log::info('✅ Finished unattended booking cleanup.');
    }
}
