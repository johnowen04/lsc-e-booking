<?php

namespace App\Traits;

trait InteractsWithSlotSelection
{
    public function isSlotBooked(array $slots, int $courtId, int $hour): bool
    {
        foreach ($slots as $row) {
            if ($row['hour'] === $hour && isset($row['slots'][$courtId]['status'])) {
                return in_array($row['slots'][$courtId]['status'], ['booked', 'held']);
            }
        }

        return false;
    }

    public function isInSelection(int $courtId, int $hour, ?int $selectedCourtId, ?int $startHour, ?int $hoverHour): bool
    {
        if ($selectedCourtId !== $courtId || is_null($startHour) || is_null($hoverHour)) {
            return false;
        }

        return $hour >= min($startHour, $hoverHour) && $hour <= max($startHour, $hoverHour);
    }

    public function isStart(int $courtId, int $hour, ?int $selectedCourtId, ?int $startHour): bool
    {
        return $selectedCourtId === $courtId && $startHour === $hour;
    }

    public function isEnd(int $courtId, int $hour, ?int $selectedCourtId, ?int $startHour, ?int $hoverHour): bool
    {
        return $selectedCourtId === $courtId && $hoverHour === $hour && $startHour !== $hoverHour;
    }

    public function selectionHasConflict(array $slots, int $courtId, int $startHour, int $endHour): bool
    {
        for ($h = $startHour; $h <= $endHour; $h++) {
            if (
                $this->isSlotInCart($this->selectedDate, $courtId, $h) ||
                $this->isSlotBooked($slots, $courtId, $h)
            ) {
                return true;
            }
        }

        return false;
    }
}
