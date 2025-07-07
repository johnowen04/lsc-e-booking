<?php

namespace App\Factories\BookingSlot;

use App\DTOs\BookingCart\SelectedSlot;
use App\DTOs\BookingSlot\CreateBookingSlotData;
use App\Models\Booking;
use App\Services\CourtSlotAvailabilityService;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotDtoFactory
{
    public function __construct(
        protected PricingRuleService $pricingRuleService,
        protected CourtSlotAvailabilityService $courtSlotAvailabilityService,
    ) {}

    public function fromSelectedSlots(Booking $booking, Collection $slots, int $courtId): array
    {
        return $slots->map(function (SelectedSlot $slot) use ($booking, $courtId) {
            $hour = Carbon::createFromTimeString("{$slot->hour}:00");

            $pricingRule = $this->pricingRuleService->getPricingRuleForHour(
                $courtId,
                $slot->date,
                $hour
            );

            $start = Carbon::parse("{$slot->date} {$slot->hour}:00");

            $courtScheduleSlot = $this->courtSlotAvailabilityService->reserve(
                $courtId,
                Carbon::parse($slot->date),
                $start
            );

            return new CreateBookingSlotData(
                bookingId: $booking->id,
                courtId: $courtId,
                date: Carbon::parse($slot->date),
                startAt: $start,
                endAt: $start->copy()->addHour(),
                price: $pricingRule->price_per_hour,
                pricingRuleId: $pricingRule->id,
                courtScheduleSlotId: $courtScheduleSlot->id,
            );
        })->toArray();
    }
}
