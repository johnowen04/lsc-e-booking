<x-filament-panels::page>
    @if ($invoice)
        <livewire:page.booking-summary-view :invoice="$invoice" :isAdmin="false" />
    @endif
</x-filament-panels::page>
