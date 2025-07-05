@props([
    'groupedSlots' => [],
    'cartTotal' => 0,
    'showActions' => false,
    'checkoutAction' => null,
])

<div class="booking-cart flex flex-col h-full">
    {{-- Header --}}
    <div class="cart-header">
        <h3 class="cart-title">{{ $title ?? '' }}</h3>

        @if (count($groupedSlots) > 0 && $showActions)
            <button wire:click="clearCart" class="clear-cart-btn">
                Clear All
            </button>
        @endif
    </div>

    @if (count($groupedSlots) === 0)
        <div class="cart-empty cart-empty-state flex-grow flex flex-col justify-center items-center text-center">
            <x-filament::icon icon="heroicon-o-shopping-bag" class="empty-icon" />
            <p class="empty-text empty-message">Your cart is empty</p>
        </div>
    @else
        <div class="cart-body flex flex-col flex-grow overflow-hidden">
            {{-- Scrollable Items --}}
            <div class="cart-items flex-grow overflow-y-auto pr-1 space-y-4">
                @foreach ($groupedSlots as $booking)
                    <x-booking.card :booking="$booking" />
                @endforeach
            </div>

            {{-- Footer --}}
            <div
                class="cart-footer cart-total-card bg-white dark:bg-gray-900 shadow rounded-xl p-6 space-y-6 mt-4 flex-shrink-0">
                <div class="cart-total flex items-center justify-between font-semibold text-lg">
                    <span>Total</span>
                    <span>Rp {{ number_format($cartTotal, 0, ',', '.') }}</span>
                </div>

                @if ($showActions)
                    <x-filament::button type="button" wire:click="{{ $checkoutAction }}" color="success"
                        class="w-full">
                        Proceed to Checkout
                    </x-filament::button>
                @endif
            </div>
        </div>
    @endif
</div>
