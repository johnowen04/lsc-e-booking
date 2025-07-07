<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\CreateBookingData;
use App\DTOs\BookingCart\SelectedSlot;
use App\DTOs\BookingInvoice\CreateBookingInvoiceData;
use App\DTOs\BookingSlot\CreateBookingSlotData;
use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\CustomerInfoData;
use App\DTOs\Shared\InvoiceReference;
use App\DTOs\Shared\MoneyData;
use App\Models\Booking;
use App\Models\BookingInvoice;
use App\Models\Payment;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\CourtSlotAvailabilityService;
use App\Services\InvoiceService;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateBookingFlow
{
    public function __construct(
        protected CourtSlotAvailabilityService $courtSlotAvailabilityService,
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected InvoiceService $invoiceService,
        protected PricingRuleService $pricingRuleService,
        protected PaymentProcessor $paymentProcessor,
    ) {}

    public function execute(array $formData, Collection $selectedSlotGroups, ?array $options = null): Payment
    {
        $createdByDto = CreatedByData::fromModel($options['creator']);
        $customerDto = CustomerInfoData::fromArray($formData);

        $callbackClass = $options['callback_class'];
        $isWalkIn = $options['is_walk_in'];
        $isPaidInFull = $formData['is_paid_in_full'];
        $paymentMethod = $formData['payment_method'];

        return DB::transaction(function () use (
            $createdByDto,
            $customerDto,
            $callbackClass,
            $isWalkIn,
            $isPaidInFull,
            $paymentMethod,
            $selectedSlotGroups,
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
                $selectedSlotGroups,
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
                new InvoiceReference(get_class($invoice), $invoice->id)
            );

            return $this->createPayment(
                $initialPaymentDto,
                fn($booking) => "Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
                $callbackClass
            );
        });
    }

    protected function createInvoice(CustomerInfoData $customer, MoneyData $amount, string $status, bool $isWalkIn, CreatedByData $createdBy): BookingInvoice
    {
        $invoiceDto = new CreateBookingInvoiceData(
            customer: $customer,
            amount: $amount,
            status: $status,
            isWalkIn: $isWalkIn,
            createdBy: $createdBy,
            issuedAt: Carbon::now(),
            dueAt: Carbon::now()->addMinutes(15),
        );

        return $this->invoiceService->createBookingInvoice($invoiceDto);
    }

    protected function createBookings(
        Collection $selectedSlotGroups,
        BookingInvoice $invoice,
        CustomerInfoData $customer,
        CreatedByData $createdBy
    ): array {
        $bookings = [];

        /** @var \Illuminate\Support\Collection<\App\DTOs\BookingCart\SelectedSlotGroup> $selectedSlotGroups */
        foreach ($selectedSlotGroups as $group) {
            $bookingDto = new CreateBookingData(
                invoiceId: $invoice->id,
                customer: $customer,
                courtId: $group->courtId,
                date: Carbon::parse($group->date),
                startsAt: Carbon::parse($group->date . ' ' . $group->startsAt),
                endsAt: Carbon::parse($group->date . ' ' . $group->endsAt),
                mustCheckInBefore: Carbon::parse($group->date . ' ' . $group->startsAt)->copy()->addMinutes(15),
                createdBy: $createdBy,
                note: null,
                rescheduledFromBookingId: null,
            );

            $booking = $this->bookingService->createBooking($bookingDto);

            $slots = $this->createBookingSlots($booking, $group->slots, $group->courtId);

            $booking->update([
                'total_price' => collect($slots)->sum('price'),
            ]);

            $bookings[] = $booking;
        }

        return $bookings;
    }

    protected function createBookingSlots(Booking $booking, Collection $slotPayloads, int $courtId): array
    {
        $slotDtos = $slotPayloads->map(function (SelectedSlot $slot) use ($booking, $courtId) {
            $hour = Carbon::createFromTimeString("{$slot->hour}:00");

            $pricingRule = $this->pricingRuleService->getPricingRuleForHour(
                $courtId,
                $slot->date,
                $hour
            );

            $startHour = Carbon::parse("{$slot->date} {$slot->hour}:00");
            $date = Carbon::parse($slot->date);

            $courtScheduleSlot = $this->courtSlotAvailabilityService->reserve(
                $courtId,
                $date,
                $startHour
            );

            return new CreateBookingSlotData(
                bookingId: $booking->id,
                courtId: $courtId,
                date: Carbon::parse($slot->date),
                startAt: $startHour,
                endAt: $startHour->copy()->addHour(),
                price: $pricingRule->price_per_hour,
                pricingRuleId: $pricingRule->id ?? null,
                courtScheduleSlotId: $courtScheduleSlot->id,
            );
        })->toArray();

        return $this->bookingSlotService->createBookingSlots($slotDtos);
    }

    protected function createPayment(CreatePaymentData $initialPayment, Closure|string $itemDetailName, string $callbackClass): Payment
    {
        return $this->paymentProcessor->handle(
            $initialPayment,
            $itemDetailName,
            $callbackClass
        );
    }
}
