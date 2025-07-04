<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\CreateBookingData;
use App\DTOs\BookingInvoice\CreateBookingInvoiceData;
use App\DTOs\BookingSlot\CreateBookingSlotData;
use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\CustomerInfoData;
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

abstract class AbstractBookingFlow
{
    public function __construct(
        protected CourtSlotAvailabilityService $courtSlotAvailabilityService,
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected InvoiceService $invoiceService,
        protected PricingRuleService $pricingRuleService,
        protected PaymentProcessor $paymentProcessor,
    ) {}

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
        Collection $groupedSlots,
        BookingInvoice $invoice,
        CustomerInfoData $customer,
        CreatedByData $createdBy,
    ): array {
        $bookings = [];

        foreach ($groupedSlots as $group) {
            $bookingDto = new CreateBookingData(
                invoiceId: $invoice->id,
                customer: $customer,
                courtId: $group['court_id'],
                date: Carbon::parse($group['date']),
                startsAt: Carbon::parse($group['date'] . ' ' . $group['starts_at']),
                endsAt: Carbon::parse($group['date'] . ' ' . $group['ends_at']),
                mustCheckInBefore: Carbon::parse($group['date'] . ' ' . $group['starts_at'])->copy()->addMinutes(15),
                createdBy: $createdBy,
                note: $group['note'] ?? null,
                rescheduledFromBookingId: $group['rescheduled_from_booking_id'] ?? null,
            );

            $booking = $this->bookingService->createBooking($bookingDto);

            $slots = $this->createBookingSlots($booking, $group['slots'], $group['court_id']);

            $booking->update([
                'total_price' => collect($slots)->sum('price'),
            ]);

            $bookings[] = $booking;
        }

        return $bookings;
    }

    protected function createBookingSlots(Booking $booking, Collection $slotPayloads, int $courtId): array
    {
        $slotDtos = $slotPayloads->map(function ($slot) use ($booking, $courtId) {
            $hour = Carbon::createFromTimeString("{$slot['hour']}:00");

            $pricingRule = $this->pricingRuleService->getPricingRuleForHour(
                $courtId,
                $slot['date'],
                $hour
            );

            $startHour = Carbon::parse("{$slot['date']} {$slot['hour']}:00");
            $date = Carbon::parse($slot['date']);

            $courtScheduleSlot = $this->courtSlotAvailabilityService->reserve(
                $courtId,
                $date,
                $startHour
            );

            return new CreateBookingSlotData(
                bookingId: $booking->id,
                courtId: $courtId,
                date: Carbon::parse($slot['date']),
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

    abstract public function execute(array $formData, Collection $groupedSlots, array $options = []): Payment;
}
