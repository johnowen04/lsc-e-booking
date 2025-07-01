<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\PricingRule;
use Carbon\Carbon;

class BookingSlotService
{
    public function checkSlotConflict(
        int $courtId,
        Carbon $start,
        Carbon $end
    ) {
        return BookingSlot::query()
            ->where('court_id', $courtId)
            ->whereIn('status', ['confirmed', 'held'])
            ->where(
                fn($query) =>
                $query->where('start_at', '<', $end)
                    ->where('end_at', '>', $start)
            )
            ->exists();
    }

    public function createBookingSlots(
        Booking $booking,
        PricingRuleService $pricingRuleService
    ): array {
        $bookingSlots = [];
        $hour = $booking->starts_at->copy();

        while ($hour < $booking->ends_at) {
            $pricingRule = $pricingRuleService->getPricingRuleForHour(
                $booking->court_id,
                $booking->date->toDateString(),
                $hour
            );

            $bookingSlot = $this->createBookingSlot(
                $booking,
                $booking->court_id,
                $booking->date->toDateString(),
                $hour,
                $hour->copy()->addHour(),
                $pricingRule
            );

            $bookingSlots[] = $bookingSlot;
            $hour->addHour();
        }

        return $bookingSlots;
    }

    protected function createBookingSlot(
        Booking $booking,
        int $courtId,
        string $date,
        Carbon $startsAt,
        Carbon $endsAt,
        PricingRule $pricingRule,
        array $overrides = [],
    ): BookingSlot {
        return BookingSlot::create(array_merge(
            [
                'booking_id' => $booking->id,
                'court_id' => $courtId,
                'date' => $date,
                'start_at' => $startsAt,
                'end_at' => $endsAt,
                'status' => 'held',
                'price' => $pricingRule->price_per_hour,
                'pricing_rule_id' => $pricingRule->id,
            ],
            $overrides
        ));
    }
}
