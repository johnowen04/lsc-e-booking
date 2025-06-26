<div class="space-y-4">
    <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white"></h3>
        @if (count($groupedSlots) > 0)
            <button wire:click="clearCart"
                class="text-sm text-danger-600 dark:text-danger-400 hover:text-danger-800 dark:hover:text-danger-300">
                Clear All
            </button>
        @endif
    </div>

    @if (count($groupedSlots) === 0)
        <div class="text-center py-8">
            <x-filament::icon icon="heroicon-o-shopping-bag" class="h-6 w-6 mx-auto text-gray-400" />
            <p class="mt-2 text-gray-500 dark:text-gray-400">Your cart is empty</p>
        </div>
    @else
        <div class="flex flex-col max-h-[90vh]">
            <div class="space-y-4 overflow-y-auto pr-1" style="min-height: 70vh; max-height: 70vh;">
                @foreach ($groupedSlots as $booking)
                    <x-booking.card :booking="$booking" />
                @endforeach
            </div>

            <div class="mt-4 pt-4 border-t dark:border-gray-700">
                <div class="flex justify-between text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <div>Total</div>
                    <div>Rp {{ number_format($cartTotal, 0, ',', '.') }}</div>
                </div>

                <x-filament::button type="button" wire:click="proceedToCheckout" color="success" class="w-full">
                    Proceed to Checkout
                </x-filament::button>
            </div>
        </div>
    @endif
</div>
