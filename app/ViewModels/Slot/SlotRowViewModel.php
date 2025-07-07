<?php

namespace App\ViewModels\Slot;

use Illuminate\Support\Collection;

class SlotRowViewModel
{
    public function __construct(
        public readonly string $time,
        public readonly int $hour,
        public readonly Collection $cells,
    ) {}
}
