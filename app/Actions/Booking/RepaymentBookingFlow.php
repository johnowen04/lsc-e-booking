<?php

namespace App\Actions\Booking;

use App\Enums\PaymentMethod;
use App\Models\BookingInvoice;
use App\Models\Payment;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepaymentBookingFlow
{
    public function __construct(
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected InvoiceService $invoiceService,
        protected PaymentService $paymentService,
        protected MidtransService $midtransService,
    ) {}

    public function execute(array $formData, BookingInvoice $invoice, ?array $options = null): Payment
    {
        $createdByType = $options['created_by_type'];
        $createdById = $options['created_by_id'];
        $callbackClass = $options['callback_class'];

        return DB::transaction(function () use (
            $formData,
            $invoice,
            $createdByType,
            $createdById,
            $callbackClass,
        ) {
            $paymentMethod = PaymentMethod::from($formData['payment_method']);

            $payment = $this->paymentService->createPayment(
                $formData['amount'],
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
                    fn($booking) => "Repayment Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
                    false,
                    $callbackClass,
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
                    $callbackClass::getUrl(['order_id' => $payment->uuid, 'status_code' => 200]),
                    now()->addMinutes(5) //ttl
                );
            } else {
                throw ValidationException::withMessages(['payment_method' => 'Invalid payment method selected.']);
            }

            return $payment;
        });
    }
}
