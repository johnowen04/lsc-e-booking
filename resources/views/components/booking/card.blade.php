@props(['booking'])

<div class="p-4 border rounded-lg bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="flex justify-between items-start">
        <div>
            <h4 class="font-medium text-primary-600 dark:text-primary-400">
                {{ $booking['court_name'] }}
            </h4>
            <div class="flex flex-wrap items-center gap-1 text-sm text-white-600 dark:text-white-300">
                <span>{{ $booking['formatted_date'] }}</span>
                <span>•</span>
                <span class="font-medium">
                    {{ $booking['start_time'] }} - {{ $booking['end_time'] }}
                </span>
                <span class="text-xs text-white-500 dark:text-white-400 ml-1">
                    ({{ $booking['duration'] }} hour{{ $booking['duration'] > 1 ? 's' : '' }})
                </span>
            </div>
            @if ($booking['price'] > 0)
                <div class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Rp {{ number_format($booking['price'], 0, ',', '.') }}
                </div>
            @else
                <div class="mt-1 text-sm font-medium text-red-500 dark:text-red-400">
                    Price not available
                </div>
            @endif
        </div>

        <button wire:click="removeSlot('{{ $booking['slots'][0]['id'] }}')"
            class="group transition-colors duration-200 ease-in-out">
            <x-filament::icon icon="heroicon-o-trash" class="w-5 h-5 text-gray-400 group-hover:text-danger-500" />
        </button>
    </div>
</div>
