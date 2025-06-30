<?php

namespace App\Actions\Booking;

use App\Enums\PaymentMethod;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateBookingFlow extends AbstractBookingFlow
{
    public function __construct(
        BookingService $bookingService,
        BookingSlotService $bookingSlotService,
        InvoiceService $invoiceService,
        PaymentService $paymentService,
        protected MidtransService $midtransService,
    ) {
        parent::__construct($bookingService, $bookingSlotService, $invoiceService, $paymentService);
    }

    public function execute(array $formData, array $groupedSlots, ?Customer $customer = null, ?array $options = null): Payment
    {
        $createdByType = $options['created_by_type'];
        $createdById = $options['created_by_id'];
        $callbackClass = $options['callback_class'];

        return DB::transaction(function () use (
            $formData,
            $groupedSlots,
            $customer,
            $createdByType,
            $createdById,
            $callbackClass,
        ) {
            $isPaidInFull = $formData['is_paid_in_full'];
            $paymentMethod = PaymentMethod::from($formData['payment_method']);

            $invoice = $this->createInvoice(
                $formData,
                $customer,
                [
                    'created_by_type' => $createdByType,
                    'created_by_id' => $createdById
                ]
            );

            $invoice = $this->createBookings(
                $groupedSlots,
                $invoice,
                [
                    'customer_id' => $customer?->id,
                    'customer_name' => $formData['customer_name'],
                    'customer_phone' => $formData['customer_phone'],
                    'created_by_type' => $createdByType,
                    'created_by_id' => $createdById
                ]
            );

            $payment = $this->paymentService->createPayment(
                $isPaidInFull ? $invoice->total_amount : (int) round($invoice->total_amount / 2, -2),
                $invoice,
                [
                    'created_by_type' => $createdByType,
                    'created_by_id' => $createdById
                ]
            );

            if ($paymentMethod === PaymentMethod::QRIS) {
                $this->midtransService->prepareSnapTransaction(
                    $invoice,
                    $payment,
                    fn($booking) => "Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
                    $isPaidInFull,
                    $callbackClass::getUrl(['order_id' => $payment->uuid]),
                    $callbackClass::getUrl(['order_id' => $payment->uuid]),
                );
            } else if ($paymentMethod === PaymentMethod::CASH) {
                $payment = $this->paymentService->updatePayment(
                    $payment->uuid,
                    (float) $payment->amount,
                    PaymentMethod::CASH->value,
                    'paid',
                    $invoice,
                    paidAt: now()->toDateTimeString(),
                    overrides: [
                        'created_by_type' => $createdByType,
                        'created_by_id' => $createdById
                    ],
                );

                Cache::put(
                    "cash_{$payment->uuid}",
                    $callbackClass::getUrl(['order_id' => $payment->uuid]),
                    now()->addMinutes(5) //ttl
                );
            } else {
                throw ValidationException::withMessages(['payment_method' => 'Invalid payment method selected.']);
            }

            return $payment;
        });
    }
}
