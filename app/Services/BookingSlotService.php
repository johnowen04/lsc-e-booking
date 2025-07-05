<?php

namespace App\Services;

use App\DTOs\BookingSlot\CreateBookingSlotData;
use App\Models\BookingSlot;
use Carbon\Carbon;

class BookingSlotService
{
    /**
     * Check if a court has a booking slot conflict in the given time range.
     */
    public function checkSlotConflict(
        int $courtId,
        Carbon $start,
        Carbon $end,
        array $excludeSlotIds = []
    ): bool {
        return BookingSlot::query()
            ->where('court_id', $courtId)
            ->whereIn('status', ['confirmed', 'held'])
            ->where(function ($query) use ($start, $end) {
                $query->where('start_at', '<', $end)
                    ->where('end_at', '>', $start);
            })
            ->when(!empty($excludeSlotIds), fn($q) => $q->whereNotIn('id', $excludeSlotIds))
            ->exists();
    }

    public function createBookingSlots(
        array $bookingSlotsDto,
    ): array {
        $bookingSlots = [];

        foreach ($bookingSlotsDto as $slotDto) {
            $slot = $this->createBookingSlot($slotDto);
            $bookingSlots[] = $slot;
        }

        return $bookingSlots;
    }

    protected function createBookingSlot(
        CreateBookingSlotData $slotData,
    ): BookingSlot {
        return BookingSlot::create(
            [
                'booking_id' => $slotData->bookingId,
                'court_id' => $slotData->courtId,
                'date' => $slotData->date,
                'start_at' => $slotData->startAt,
                'end_at' => $slotData->endAt,
                'status' => $slotData->status,
                'price' => $slotData->price,
                'pricing_rule_id' => $slotData->pricingRuleId,
                'court_schedule_slot_id' => $slotData->courtScheduleSlotId,
            ]
        );
    }
}
