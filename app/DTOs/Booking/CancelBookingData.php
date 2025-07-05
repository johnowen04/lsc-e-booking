<?php

namespace App\DTOs\Booking;

use App\DTOs\Shared\CreatedByData;
use App\Models\Booking;

class CancelBookingData
{
    public function __construct(
        public Booking $booking,
        public string $rescheduleReason = '',
        public CreatedByData $cancelledBy,
    ) {}
}
