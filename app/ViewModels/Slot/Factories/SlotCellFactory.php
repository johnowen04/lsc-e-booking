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
        bool $inCart,
        bool $isOriginal = false,
        array $allowedPrices = [],
    ): SlotCellViewModel {
        $hour = $slotData->hour;
        $isStart = $selectedCourtId === $slotData->courtId && $selectedStartHour === $hour;

        $isInSelection = $isStart ||
            ($hoverHour !== null &&
                $slotData->courtId === $selectedCourtId &&
                $hour >= $selectedStartHour &&
                $hour <= $hoverHour);

        $isEnd = $isInSelection && $hour === $hoverHour;

        $isAllowedPrice = empty($allowedPrices) || in_array($slotData->price, $allowedPrices);

        $isDisabled = $isOriginal
            ? false
            : (
                $slotData->status !== 'available'
                || $inCart
                || !$slotData->isBookable
                || !$isAllowedPrice
            );

        $pricingRuleName = $slotData->pricingRuleName ?? null;

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
            isOriginal: $isOriginal,
            label: self::makeSlotLabel($slotData->status, $inCart, $isStart, $isEnd, $isInSelection, $isDisabled, $isOriginal),
            classes: self::makeSlotClasses($slotData->status, $inCart, $isStart, $isEnd, $isInSelection, $isDisabled, $isOriginal),
            pricingRuleName: $pricingRuleName,
        );
    }

    protected static function makeSlotLabel(
        string $status,
        bool $inCart,
        bool $isStart,
        bool $isEnd,
        bool $isInSelection,
        bool $isDisabled,
        bool $isOriginal,
    ): string {
        return match (true) {
            $isStart => 'Start',
            $isEnd => 'End',
            $inCart => 'In Cart',
            $isInSelection => 'Picked',
            $isOriginal => 'Book (Original)',
            $status === 'booked' => 'Booked',
            $status === 'held' => 'Held',
            $isDisabled && $inCart => 'In Cart',
            $isDisabled => 'Unavailable',
            default => 'Book',
        };
    }

    protected static function makeSlotClasses(
        string $status,
        bool $inCart,
        bool $isStart,
        bool $isEnd,
        bool $isInSelection,
        bool $isDisabled,
        bool $isOriginal,
    ): array {
        $classes = [$status];

        if ($isOriginal) $classes[] = 'original';
        if ($inCart) $classes[] = 'in-cart';
        if ($isInSelection) $classes[] = 'in-selection';
        if ($isStart || $isEnd) $classes[] = 'is-range-endpoint';
        if ($isDisabled) $classes[] = 'disabled';

        return array_unique($classes);
    }
}
