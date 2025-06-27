<x-filament-panels::page>
    <div x-data="{ showCartDrawer: false }" x-init="window.addEventListener('slotsAddedToCart', () => showCartDrawer = true)">

        {{-- Date Navigator --}}
        <div :class="{ 'opacity-40 pointer-events-none filter blur-sm': showCartDrawer }"
            class="pr-0 w-full transition-all duration-300">
            <livewire:booking-date-navigator wire:model.live="selectedDate" />
        </div>

        {{-- Booking Slot Grid --}}
        <div :class="{ 'opacity-40 pointer-events-none filter blur-sm': showCartDrawer }"
            class="pr-0 w-full transition-all duration-300">
            <livewire:booking-slot-grid wire:model="selectedDate" />
        </div>

        {{-- Floating Cart Button --}}
        <div
            :style="`position: fixed; top: 105px; right: ${showCartDrawer ? '33%' : '0'}; z-index: 9999; transition: right 0.3s ease;`">
            <button x-on:click="showCartDrawer = !showCartDrawer"
                :class="showCartDrawer ? 'right-[33%] hidden lg:block' : 'right-0'"
                style="background-color: #10b981; color: white; padding: 0.5rem 1rem; border-top-left-radius: 0.5rem; border-bottom-left-radius: 0.5rem;">
                <x-filament::icon icon="heroicon-o-shopping-bag" class="h-6 w-6" />
            </button>
        </div>

        {{-- Overlay --}}
        <div x-show="showCartDrawer" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-900/50 z-40"
            x-on:click="showCartDrawer = false">
        </div>

        {{-- Cart Drawer --}}
        <div x-show="showCartDrawer" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
            class="booking-form-drawer fixed top-0 right-0 w-full sm:w-2/3 md:w-1/2 lg:w-1/3 xl:w-1/4 h-full bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 shadow-xl z-50 overflow-y-auto p-4">
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white flex items-center">
                        <x-filament::icon icon="heroicon-o-shopping-bag" class="w-5 h-5 me-1" />
                        Cart
                    </h2>
                    <button x-on:click="showCartDrawer = false"
                        class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 text-lg">
                        &times;
                    </button>
                </div>

                <livewire:booking-cart />
            </div>
        </div>
    </div>
</x-filament-panels::page>
