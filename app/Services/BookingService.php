<?php

namespace App\Services;

use App\DTOs\Booking\CancelBookingData;
use App\DTOs\Booking\CreateBookingData;
use App\Models\Booking;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct() {}

    public function createBooking(CreateBookingData $data): Booking
    {
        return Booking::create([
            'uuid' => Str::uuid(),
            'booking_number' => null,
            'booking_invoice_id' => $data->invoiceId,
            'customer_id' => $data->customer->id,
            'customer_name' => $data->customer->name,
            'customer_email' => $data->customer->email,
            'customer_phone' => $data->customer->phone,
            'court_id' => $data->courtId,
            'date' => $data->date->toDateString(),
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'total_price' => $data->totalPrice,
            'status' => $data->status,
            'attendance_status' => $data->attendanceStatus,
            'must_check_in_before' => $data->mustCheckInBefore,
            'note' => $data->note,
            'created_by_type' => $data->createdBy?->type,
            'created_by_id' => $data->createdBy?->id,
            'rescheduled_from_booking_id' => $data->rescheduledFromBookingId,
        ]);
    }

    public function cancelBookingAndReleaseSlots(CancelBookingData $data): void
    {
        $data->booking->update([
            'status' => 'cancelled',
            'reschedule_reason' => $data->rescheduleReason,
            'rescheduled_by_type' => $data->cancelledBy->type,
            'rescheduled_by_id' => $data->cancelledBy->id,
        ]);

        $data->booking->slots()->update([
            'status' => 'cancelled',
        ]);
    }
}
