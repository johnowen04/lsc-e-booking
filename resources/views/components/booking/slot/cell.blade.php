@props(['cell', 'selectedCourtId' => null, 'selectedStartHour' => null])

<td class="booking-slot-cell {{ implode(' ', $cell->classes ?? []) }}"
    @if (!($cell->isDisabled ?? true)) wire:click="selectSlot({{ $cell->courtId }}, {{ $cell->hour }})"
        @if ($selectedCourtId !== null && $selectedStartHour !== null)
            wire:mouseover="setHoverHour({{ $cell->hour }})" @endif
    @endif>
    <div class="booking-slot-content">
        <span class="slot-label">{{ $cell->label ?? '' }}</span>

        @if (!empty($cell->price))
            <span class="slot-price">
                Rp {{ number_format($cell->price, 0, ',', '.') }}
            </span>
        @endif

        <span class="hidden text-xs font-semibold rounded px-1 py-0.5 bg-primary-100 text-primary-700">
            {{ $cell->pricingRuleName }}
        </span>
    </div>
</td>
