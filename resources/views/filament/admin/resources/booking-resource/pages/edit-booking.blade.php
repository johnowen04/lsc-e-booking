<x-filament::page>
    <link rel="stylesheet" href="{{ asset('css/livewire-component/theme.css') }}">
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="w-full lg:w-1/2 pt-0">
            <x-filament::section>
                <x-slot name="heading">Schedules</x-slot>
                <livewire:page.booking.reschedule.booking-reschedule-cart :groupedSlots="$groupedSlots->toArray()" :rescheduleUrl="'https://google.com'"
                    :showActions="false" :original-booking="$originalBooking" :immutable="true" />
            </x-filament::section>
        </div>

        <div class="w-full lg:w-1/2">
            <x-filament::section>
                <x-slot name="heading">
                    Reschedule Details
                </x-slot>
                <form wire:submit.prevent="save">
                    <div class="space-y-6">
                        <div>
                            {{ $this->form }}
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit">
                            Reschedule
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>
    </div>
</x-filament::page>
