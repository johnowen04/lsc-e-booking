<?php

namespace App\ViewModels\Slot\Factories;

use App\DTOs\Slot\SlotData;
use App\ViewModels\Slot\SlotCellViewModel;

class SlotCellFactory
{
    public static function fromSlotData(
        SlotData $slotData,
        int $selectedCourtId,
        ?int $selectedStartHour,
        ?int $hoverHour,
        bool $inCart
    ): SlotCellViewModel {
        $hour = $slotData->hour;
        $isStart = $selectedCourtId === $slotData->courtId && $selectedStartHour === $hour;

        $isInSelection = $isStart ||
            ($hoverHour !== null &&
                $slotData->courtId === $selectedCourtId &&
                $hour >= $selectedStartHour &&
                $hour <= $hoverHour);

        $isEnd = $isInSelection && $hour === $hoverHour;

        $isDisabled = $slotData->status !== 'available' || $inCart || !$slotData->isBookable;

        return new SlotCellViewModel(
            courtId: $slotData->courtId,
            hour: $hour,
            status: $slotData->status,
            price: $slotData->price,
            inCart: $inCart,
            isBookable: $slotData->isBookable,
            isStart: $isStart,
            isEnd: $isEnd,
            isInSelection: $isInSelection,
            isDisabled: $isDisabled,
            label: self::makeSlotLabel($slotData->status, $inCart, $isStart, $isEnd, $isInSelection, $isDisabled),
            classes: self::makeSlotClasses($slotData->status, $inCart, $isStart, $isEnd, $isInSelection, $isDisabled),
        );
    }

    protected static function makeSlotLabel(
        string $status,
        bool $inCart,
        bool $isStart,
        bool $isEnd,
        bool $isInSelection,
        bool $isDisabled
    ): string {
        return match (true) {
            $status === 'booked' => 'Booked',
            $status === 'held' => 'Held',
            $isDisabled && $inCart => 'In Cart',
            $isDisabled => 'Unavailable',
            $isStart => 'Start',
            $isEnd => 'End',
            $inCart => 'In Cart',
            $isInSelection => 'Picked',
            default => 'Book',
        };
    }

    protected static function makeSlotClasses(
        string $status,
        bool $inCart,
        bool $isStart,
        bool $isEnd,
        bool $isInSelection,
        bool $isDisabled
    ): array {
        $classes = [$status];

        if ($inCart) $classes[] = 'in-cart';
        if ($isInSelection) $classes[] = 'in-selection';
        if ($isStart || $isEnd) $classes[] = 'is-range-endpoint';
        if ($isDisabled) $classes[] = 'disabled';

        return array_unique($classes);
    }
}
