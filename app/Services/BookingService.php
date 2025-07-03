<?php

namespace App\Services;

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
            'customer_id' => $data->customer->id,
            'customer_name' => $data->customer->name,
            'customer_email' => $data->customer->email,
            'customer_phone' => $data->customer->phone,
            'court_id' => $data->courtId,
            'date' => $data->date->toDateString(),
            'starts_at' => $data->startsAt,
            'ends_at' => $data->endsAt,
            'must_check_in_before' => $data->mustCheckInBefore,
            'total_price' => $data->totalPrice,
            'status' => $data->status,
            'attendance_status' => $data->attendanceStatus,
            'note' => $data->note,
            'rescheduled_from_booking_id' => $data->rescheduledFromBookingId,
            'booking_invoice_id' => $data->invoiceId,
            'created_by_type' => $data->createdBy?->type,
            'created_by_id' => $data->createdBy?->id,
        ]);
    }
}
