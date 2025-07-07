@php
    $originalTotal = (float) $originalBooking->total_price;
    $cartTotal = (float) collect($groupedSlots)->sum('price'); // adjust as needed
    $totalsMatch = $originalTotal === $cartTotal;
@endphp

<div class="relative border-l-2 border-primary-500 pl-6">
    {{-- Load Theme Styles --}}
    <link rel="stylesheet" href="{{ asset('css/livewire-component/theme.css') }}">

    {{-- 📌 Original Booking --}}
    <div class="flex items-start gap-4">
        <div class="mt-1 w-3 h-3 rounded-full bg-primary-500 border-2 border-white dark:border-gray-800"></div>
        <div class="flex-1">
            <div class="text-xs text-gray-400 uppercase mb-1">Original Booking</div>
            <x-booking.reschedule-card :booking="$originalBooking" />
        </div>
    </div>

    {{-- 🔀 Arrow Indicator --}}
    <div class="flex items-start gap-4 mt-6">
        <div class="mt-1 w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></div>
        <div class="flex-1">
            <span class="uppercase tracking-wide text-xs text-gray-500">Rescheduling to</span>
        </div>
    </div>

    {{-- 🆕 New Booking Slots --}}
    @forelse ($this->getGroupedSlots() as $booking)
        <div class="flex items-start gap-4 mt-6">
            <div class="mt-1 w-3 h-3 rounded-full bg-green-500 border-2 border-white dark:border-gray-800"></div>
            <div class="flex-1">
                <div class="text-xs text-gray-400 uppercase mb-1">New Booking</div>
                <x-booking.card :booking="$booking" :immutable="$immutable" />
            </div>
        </div>
    @empty
        <div class="flex items-start gap-4 mt-6">
            <div class="mt-1 w-3 h-3 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            <div class="flex-1 text-sm text-gray-400 italic">No new slots selected yet.</div>
        </div>
    @endforelse

    {{-- 🧮 Totals & Reschedule Button --}}
    @if ($showActions)
        <div class="pt-4 mt-8 border-t border-gray-200 dark:border-gray-700 space-y-4">
            {{-- Totals --}}
            <div class="text-sm text-gray-600 dark:text-gray-300 text-end">
                <div>Original Total: <strong>Rp{{ number_format($originalTotal, 0, ',', '.') }}</strong></div>
                <div>New Cart Total: <strong>Rp{{ number_format($cartTotal, 0, ',', '.') }}</strong></div>
            </div>

            {{-- Button --}}
            <div class="flex justify-center">
                @if ($totalsMatch)
                    <x-filament::button type="button" wire:click="proceedToReschedule" color="success" class="w-full">
                        Proceed to Reschedule
                    </x-filament::button>
                @else
                    <x-filament::button :disabled="true" type="button" wire:click="proceedToReschedule" color="danger"
                        class="w-full cursor-not-allowed">
                        Total mismatch — cannot proceed
                    </x-filament::button>
                @endif
            </div>
        </div>
    @endif
    </div>
