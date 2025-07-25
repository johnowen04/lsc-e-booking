<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/livewire-component/theme.css') }}">
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-900 p-4 mb-6 rounded shadow">
        <p class="font-medium">
            ⚠️ Harap hadir maksimal 15 menit setelah waktu booking dimulai. Jika tidak, booking Anda akan otomatis
            dibatalkan atau dianggap hangus.
        </p>
    </div>
    <div class="flex flex-col lg:flex-row gap-4">
        <div class="w-full lg:w-1/2 pt-0">
            <x-filament::section>
                <x-slot name="heading">Schedules</x-slot>
                <livewire:page.booking.booking-cart :showActions="false" />
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

                        @if (!$data['is_paid_in_full'])
                            <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                                <div>Down Payment (50%)</div>
                                <div>Rp {{ number_format($cartTotal / 2, 0, ',', '.') }}</div>
                            </div>
                        @else
                            <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white">
                                <div>Total</div>
                                <div>Rp {{ number_format($cartTotal, 0, ',', '.') }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex justify-end">
                        <x-filament::button type="submit">
                            Checkout
                        </x-filament::button>
                    </div>
                </form>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
