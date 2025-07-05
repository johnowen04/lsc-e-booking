<?php

namespace App\DTOs\Booking;

use App\DTOs\Shared\CustomerInfoData;
use App\DTOs\Shared\CreatedByData;
use Carbon\Carbon;

class CreateBookingData
{
    public function __construct(
        public int $invoiceId,
        public CustomerInfoData $customer,
        public int $courtId,
        public Carbon $date,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public Carbon $mustCheckInBefore,
        public CreatedByData $createdBy,
        public float $totalPrice = 0.0,
        public string $status = 'held',
        public string $attendanceStatus = 'pending',
        public ?string $note = null,
        public ?int $rescheduledFromBookingId = null,
    ) {}
}
