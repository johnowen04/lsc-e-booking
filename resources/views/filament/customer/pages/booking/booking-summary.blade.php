<x-filament-panels::page>
    @if ($invoice)
        <livewire:page.booking.booking-summary-view :invoice="$invoice" :isAdmin="false" />
    @endif
</x-filament-panels::page>
