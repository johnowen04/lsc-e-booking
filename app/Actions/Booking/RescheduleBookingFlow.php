<?php

namespace App\Actions\Booking;

use App\DTOs\Booking\RescheduleBookingData;
use App\DTOs\BookingCart\SelectedSlotGroup;
use App\DTOs\Shared\CreatedByData;
use App\Factories\BookingSlot\SlotDtoFactory;
use App\Models\Booking;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\CourtSlotAvailabilityService;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RescheduleBookingFlow
{
    public function __construct(
        protected CourtSlotAvailabilityService $courtSlotAvailabilityService,
        protected BookingService $bookingService,
        protected BookingSlotService $bookingSlotService,
        protected PricingRuleService $pricingRuleService,
        protected PaymentProcessor $paymentProcessor,
        protected SlotDtoFactory $slotDtoFactory,
    ) {}

    public function execute(array $formData, Booking $originalBooking, SelectedSlotGroup $selectedSlotGroup, ?array $options = null): Booking
    {
        $createdByDto = CreatedByData::fromModel($options['creator']);

        return DB::transaction(function () use (
            $formData,
            $originalBooking,
            $selectedSlotGroup,
            $createdByDto,
        ) {
            $rescheduleData = new RescheduleBookingData(
                originalBooking: $originalBooking,
                rescheduleReason: $formData['reschedule_reason'],
                courtId: $selectedSlotGroup->courtId,
                date: Carbon::parse($selectedSlotGroup->date),
                startsAt: Carbon::parse($selectedSlotGroup->date . ' ' . $selectedSlotGroup->startsAt),
                endsAt: Carbon::parse($selectedSlotGroup->date . ' ' . $selectedSlotGroup->endsAt),
                mustCheckInBefore: Carbon::parse($selectedSlotGroup->date . ' ' . $selectedSlotGroup->startsAt)->copy()->addMinutes(15),
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
                $selectedSlotGroup->slots,
                $selectedSlotGroup->courtId
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

    protected function createBookingSlots(Booking $booking, Collection $slots, int $courtId): array
    {
        $slotDtos = $this->slotDtoFactory->fromSelectedSlots($booking, $slots, $courtId);
        return $this->bookingSlotService->createBookingSlots($slotDtos);
    }
}
