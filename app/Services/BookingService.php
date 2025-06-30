<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingInvoice;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct() {}

    public function createBooking($bookingGroup, BookingInvoice $invoice, array $overrides = []): Booking
    {
        return Booking::create(array_merge(
            [
                'uuid' => Str::uuid(),
                'customer_id' => null,
                'customer_name' => 'Guest',
                'customer_phone' => '081234567890',
                'court_id' => $bookingGroup['court_id'],
                'date' => $bookingGroup['date'],
                'starts_at' => Carbon::parse($bookingGroup['date'] . $bookingGroup['start_time']),
                'ends_at' => Carbon::parse($bookingGroup['date'] . $bookingGroup['end_time']),
                'must_check_in_before' => Carbon::parse($bookingGroup['date'] . $bookingGroup['start_time'])->addMinutes(15), //ttl
                'status' => 'held',
                'attendance_status' => 'pending',
                'booking_invoice_id' => $invoice->id,
            ],
            $overrides
        ));
    }
}
