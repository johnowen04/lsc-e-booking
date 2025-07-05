<?php

namespace App\DTOs\Booking;

use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\CustomerInfoData;
use App\Models\Booking;
use Carbon\Carbon;

class RescheduleBookingData
{
    public function __construct(
        public Booking $originalBooking,
        public string $rescheduleReason,
        public int $courtId,
        public Carbon $date,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public Carbon $mustCheckInBefore,
        public ?float $totalPrice = 0.0,
        public ?string $status = 'confirmed',
        public ?string $attendanceStatus = 'pending',
        public ?string $note = null,
        public ?CreatedByData $createdBy = null,
    ) {}

    public function toCreateBookingData(): CreateBookingData
    {
        return new CreateBookingData(
            invoiceId: $this->originalBooking->booking_invoice_id,
            customer: CustomerInfoData::fromModel($this->originalBooking->customer),
            courtId: $this->courtId,
            date: $this->date,
            startsAt: $this->startsAt,
            endsAt: $this->endsAt,
            mustCheckInBefore: $this->mustCheckInBefore,
            createdBy: $this->createdBy,
            totalPrice: $this->originalBooking->totalPrice ?? $this->totalPrice,
            status: $this->status,
            attendanceStatus: $this->attendanceStatus,
            note: $this->note,
            rescheduledFromBookingId: $this->originalBooking->id
        );
    }

    public function toCancelBookingData(): CancelBookingData
    {
        return new CancelBookingData(
            booking: $this->originalBooking,
            rescheduleReason: $this->rescheduleReason,
            cancelledBy: $this->createdBy ?? CreatedByData::fromModel($this->originalBooking->createdBy),
        );
    }
}
