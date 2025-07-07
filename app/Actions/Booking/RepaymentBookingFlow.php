<?php

namespace App\Actions\Booking;

use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\InvoiceReference;
use App\DTOs\Shared\MoneyData;
use App\Models\BookingInvoice;
use App\Models\Payment;
use App\Processors\Payment\PaymentProcessor;
use Illuminate\Support\Facades\DB;

class RepaymentBookingFlow
{
    public function __construct(
        protected PaymentProcessor $paymentProcessor,
    ) {}

    public function execute(array $formData, BookingInvoice $invoice, ?array $options = null): Payment
    {
        $createdByDto = CreatedByData::fromModel($options['creator']);
        $paymentMethod = $formData['payment_method'];
        $callbackClass = $options['callback_class'];

        return DB::transaction(function () use (
            $invoice,
            $createdByDto,
            $paymentMethod,
            $callbackClass
        ) {
            $initialPaymentDto = new CreatePaymentData(
                new MoneyData(
                    total: round($invoice->total_amount / 2, -2),
                    paid: round($invoice->total_amount / 2, -2),
                ),
                $paymentMethod,
                $createdByDto,
                new InvoiceReference(get_class($invoice), $invoice->id)
            );

            return $this->paymentProcessor->handle(
                $initialPaymentDto,
                fn($booking) => "Repayment Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
                $callbackClass
            );
        });
    }
}
