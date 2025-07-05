<x-filament-panels::page>
    <livewire:page.booking.reschedule.booking-reschedule-view :rescheduleUrl="App\Filament\Admin\Resources\BookingResource::getUrl('edit', ['record' => $originalBooking])" :is-admin="true" :original-booking="$originalBooking"
     />
</x-filament-panels::page>
