@props([
    'isAdmin' => false,
    'redirectUrl' => $isAdmin ? \App\Filament\Admin\Resources\PricingRuleResource::getUrl('index') : null, // default to pricing rule index or admin panel
    'buttonText' => 'Go to Pricing Setup',
])

<div
    class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-300 dark:border-yellow-600 text-yellow-800 dark:text-yellow-100 p-6 rounded-2xl shadow-md">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-start gap-3">
            <x-heroicon-o-exclamation-circle class="w-6 h-6 text-yellow-500 mt-1" />
            <div>
                <h3 class="text-lg font-semibold">Missing Pricing Configuration</h3>
                <p class="text-sm mt-1">
                    Bookings are disabled for this court on the selected date because no pricing rules have been
                    defined.
                    Please configure pricing to enable booking.
                </p>
            </div>
        </div>

        @if ($isAdmin)
            <div class="flex-shrink-0">
                <a href="{{ $redirectUrl }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium shadow transition">
                    {{ $buttonText }}
                    <x-heroicon-o-arrow-right class="w-4 h-4 ml-2" />
                </a>
            </div>
        @endif
    </div>
</div>
