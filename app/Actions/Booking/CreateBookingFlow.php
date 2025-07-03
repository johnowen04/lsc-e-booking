<?php

namespace App\Actions\Booking;

use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\CustomerInfoData;
use App\DTOs\Shared\InvoiceReference;
use App\DTOs\Shared\MoneyData;
use App\Models\Payment;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PricingRuleService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateBookingFlow extends AbstractBookingFlow
{
    public function __construct(
        BookingService $bookingService,
        BookingSlotService $bookingSlotService,
        InvoiceService $invoiceService,
        PricingRuleService $pricingRuleService,
        PaymentProcessor $paymentProcessor,
    ) {
        parent::__construct($bookingService, $bookingSlotService, $invoiceService, $pricingRuleService, $paymentProcessor);
    }

    public function execute(array $formData, Collection $groupedSlots, ?array $options = null): Payment
    {
        $customerDto = CustomerInfoData::fromArray($formData);
        $createdByDto = CreatedByData::fromModel($options['creator']);
        $paymentMethod = $formData['payment_method'];
        $isPaidInFull = $formData['is_paid_in_full'];
        $isWalkIn = $options['is_walk_in'];
        $callbackClass = $options['callback_class'];

        return DB::transaction(function () use (
            $groupedSlots,
            $customerDto,
            $createdByDto,
            $paymentMethod,
            $isPaidInFull,
            $isWalkIn,
            $callbackClass
        ) {
            $invoice = $this->createInvoice(
                $customerDto,
                new MoneyData(
                    total: 0.0,
                ),
                'unpaid',
                $isWalkIn,
                $createdByDto
            );

            $bookings = $this->createBookings(
                $groupedSlots,
                $invoice,
                $customerDto,
                $createdByDto
            );

            $invoice->update(['total_amount' => collect($bookings)->sum('total_price')]);

            $initialPaymentDto = new CreatePaymentData(
                new MoneyData(
                    total: $isPaidInFull ? $invoice->total_amount : round($invoice->total_amount / 2, -2),
                    paid: $isPaidInFull ? $invoice->total_amount : round($invoice->total_amount / 2, -2),
                ),
                $paymentMethod,
                $createdByDto,
                new InvoiceReference(
                    type: get_class($invoice),
                    id: $invoice->id,
                )
            );

            return $this->createPayment(
                $initialPaymentDto,
                fn($booking) => "Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
                $callbackClass
            );
        });
    }
}
