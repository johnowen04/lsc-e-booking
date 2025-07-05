<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\RescheduleBookingData;
use App\DTOs\BookingSlot\CreateBookingSlotData;
use App\DTOs\Shared\CreatedByData;
use App\Models\Booking;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\CourtSlotAvailabilityService;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RescheduleBookingFlow
{
    public function __construct(
        protected CourtSlotAvailabilityService $courtSlotAvailabilityService,
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected PricingRuleService $pricingRuleService,
        protected PaymentProcessor $paymentProcessor,
    ) {}

    public function execute(array $formData, Booking $originalBooking, $groupedSlot, ?array $options = null): Booking
    {
        $createdByDto = CreatedByData::fromModel($options['creator']);

        return DB::transaction(function () use (
            $formData,
            $originalBooking,
            $groupedSlot,
            $createdByDto,
        ) {
            $rescheduleData = new RescheduleBookingData(
                originalBooking: $originalBooking,
                rescheduleReason: $formData['reschedule_reason'],
                courtId: $groupedSlot['court_id'],
                date: Carbon::parse($groupedSlot['date']),
                startsAt: Carbon::parse($groupedSlot['date'] . ' ' . $groupedSlot['starts_at']),
                endsAt: Carbon::parse($groupedSlot['date'] . ' ' . $groupedSlot['ends_at']),
                mustCheckInBefore: Carbon::parse($groupedSlot['date'] . ' ' . $groupedSlot['starts_at'])->copy()->addMinutes(15),
                note: $formData['note'] ?? null,
                createdBy: $createdByDto,
            );

            foreach ($originalBooking->slots as $slot) {
                if ($slot->courtScheduleSlot) {
                    $this->courtSlotAvailabilityService->release($slot->courtScheduleSlot);
                }
            }

            $this->bookingService->cancelBookingAndReleaseSlots($rescheduleData->toCancelBookingData());

            $newBooking = $this->bookingService->createBooking(
                $rescheduleData->toCreateBookingData()
            );

            $newSlots = $this->createBookingSlots(
                $newBooking,
                $groupedSlot['slots'],
                $groupedSlot['court_id']
            );

            foreach ($newSlots as $slot) {
                $slot->update([
                    'status' => 'confirmed',
                ]);

                if ($slot->courtScheduleSlot) {
                    $this->courtSlotAvailabilityService->confirm($slot->courtScheduleSlot);
                }

                $slot->save();
            }

            $newBooking->update([
                'total_price' => collect($newSlots)->sum('price'),
            ]);
            
            $newBooking->generateBookingNumber();
            $newBooking->save();

            return $newBooking;
        });
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
}
