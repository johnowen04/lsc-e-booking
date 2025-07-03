<?php

namespace App\DTOs\BookingSlot;

use Carbon\Carbon;

class CreateBookingSlotData
{
    public function __construct(
        public int $bookingId,
        public int $courtId,
        public Carbon $date,
        public Carbon $startAt,
        public Carbon $endAt,
        public float $price,
        public string $status = 'held',
        public ?int $pricingRuleId = null,
        public ?int $courtScheduleSlotId = null,
        public ?Carbon $cancelledAt = null,
        public ?Carbon $expiredAt = null,
    ) {}
}
