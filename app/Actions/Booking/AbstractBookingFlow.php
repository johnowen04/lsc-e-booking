<?php

namespace App\Actions\Booking;

use App\Models\BookingInvoice;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\PricingRuleService;

abstract class AbstractBookingFlow
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService,
    ) {}

    protected function createInvoice(array $formData, ?Customer $customer, array $overrides = []): BookingInvoice
    {
        return $this->invoiceService->createBookingInvoice(
            array_merge(
                [
                    'customer_id' => $customer?->id,
                    'customer_name' => $formData['customer_name'],
                    'customer_phone' => $formData['customer_phone'],
                    'is_walk_in' => $formData['is_walk_in'] ?? false,
                ],
                $overrides
            )
        );
    }

    protected function createBookings(array $groupedSlots, BookingInvoice $invoice, array $overrides = []): BookingInvoice
    {
        $bookings = [];

        foreach ($groupedSlots as $group) {
            $booking = $this->bookingService->createBooking(
                $group,
                $invoice,
                $overrides
            );

            $slots = $this->bookingSlotService->createBookingSlots(
                $booking,
                app(PricingRuleService::class)
            );
            $booking->update(['total_price' => collect($slots)->sum('price')]);
            $bookings[] = $booking;
        }

        $invoice->update(['total_amount' => collect($bookings)->sum('total_price')]);

        return $invoice;
    }

    abstract public function execute(array $formData, array $groupedSlots, ?Customer $customer = null, array $options = []): Payment;
}
