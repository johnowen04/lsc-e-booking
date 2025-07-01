<?php

use App\Jobs\CleanupUnpaidBookingInvoices;
use Illuminate\Console\Scheduling\Schedule;

return function (Schedule $schedule) {
    $schedule->job(CleanupUnpaidBookingInvoices::class)
        ->everyMinute()
        ->name('cleanup-unpaid-booking-invoices')
        ->withoutOverlapping();
};
