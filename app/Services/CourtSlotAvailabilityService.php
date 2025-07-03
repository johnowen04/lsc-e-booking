<?php

namespace App\Services;

use App\Models\CourtScheduleSlot;
use Carbon\Carbon;

class CourtSlotAvailabilityService
{
    /**
     * Reserve a court schedule slot by marking it as 'held'.
     *
     * @throws \Exception if the slot is not available
     */
    public function reserve(int $courtId, Carbon $date, Carbon $startAt): CourtScheduleSlot
    {
        $slot = CourtScheduleSlot::where('court_id', $courtId)
            ->whereDate('date', $date)
            ->where('start_at', $startAt)
            ->lockForUpdate()
            ->first();

        if (! $slot || $slot->status !== 'available') {
            throw new \Exception("❌ Slot for court={$courtId} at {$startAt->format('Y-m-d H:i')} is not available.");
        }

        $slot->update(['status' => 'held']);

        return $slot;
    }

    /**
     * Mark a slot as confirmed (after payment success).
     */
    public function confirm(CourtScheduleSlot $slot): void
    {
        if ($slot->status !== 'held') {
            throw new \Exception("Cannot confirm slot: status is '{$slot->status}', expected 'held'.");
        }

        $slot->update(['status' => 'confirmed']);
    }

    /**
     * Release a previously held/confirmed slot back to available (e.g. expired, no-show).
     */
    public function release(CourtScheduleSlot $slot): void
    {
        if (!in_array($slot->status, ['held', 'confirmed'])) {
            throw new \Exception("Cannot release slot: status is '{$slot->status}', must be 'held' or 'confirmed'.");
        }

        $slot->update(['status' => 'available']);
    }
}
