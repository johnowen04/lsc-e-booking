<?php

namespace App\ViewModels\Slot;

class SlotCellViewModel
{
    public function __construct(
        public readonly int $courtId,
        public readonly int $hour,
        public readonly string $status,
        public readonly int $price,
        public readonly bool $inCart,
        public readonly bool $isBookable,
        public readonly bool $isStart,
        public readonly bool $isEnd,
        public readonly bool $isInSelection,
        public readonly bool $isDisabled,
        public readonly bool $isOriginal,
        public readonly string $label,
        public readonly array $classes,
        public ?string $pricingRuleName = null,
    ) {}
}
