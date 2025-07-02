<?php

use App\Jobs\CleanupUnattendedBookings;
use App\Jobs\CleanupUnpaidBookingInvoices;
use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->job(CleanupUnpaidBookingInvoices::class)
        ->everyMinute()
        ->name('cleanup-unpaid-booking-invoices')
        ->withoutOverlapping();

    $schedule->job(CleanupUnattendedBookings::class)
        ->everyMinute()
        ->name('cleanup-unattended-bookings')
        ->withoutOverlapping();
};
