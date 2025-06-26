<x-filament::page>
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="w-full lg:w-1/2">
            <x-filament::section>
                <x-slot name="heading">
                    Schedules
                </x-slot>
                <div class="space-y-4">
                    @if (empty($groupedSlots) || count($groupedSlots) === 0)
                        <div class="text-center py-8">
                            <x-filament::icon icon="heroicon-o-shopping-bag" class="h-6 w-6 mx-auto text-grey-400" />
                            <p class="mt-2 text-grey-500 dark:text-white-400">No items in cart</p>

                            <div class="py-4">
                                <x-filament::button tag="a"
                                    href="{{ route('filament.admin.pages.booking-schedule') }}">
                                    Select time slots
                                </x-filament::button>
                            </div>
                        </div>
                    @else
                        @foreach ($groupedSlots as $booking)
                            <x-booking.card :booking="$booking" />
                        @endforeach
                    @endif
                </div>
            </x-filament::section>
        </div>

        <div class="w-full lg:w-1/2">
            <x-filament::section>
                <x-slot name="heading">
                    Booking Details
                </x-slot>
                <form wire:submit.prevent="create">
                    <div class="space-y-6">
                        <div>
                            {{ $this->form }}
                        </div>

                        <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                            <div>Total</div>
                            <div>Rp {{ number_format($cartTotal, 0, ',', '.') }}</div>
                        </div>

                        @if (!$data['is_paid_in_full'])
                            <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                                <div>Down Payment (50%)</div>
                                <div>Rp {{ number_format($cartTotal / 2, 0, ',', '.') }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit">
                            {{ $this->getSubmitFormAction()->getLabel() }}
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>
    </div>
</x-filament::page>
