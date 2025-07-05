@props(['booking' => null])

@php
    $uid = uniqid('collapse_');
    $grouped = $booking->slotsGroupedByPricingRule();
    $duration = $booking->starts_at->diffInHours($booking->ends_at);
@endphp

<div x-data="{ open: false }"
    class="p-4 border rounded-lg bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="flex justify-between items-start">
        <div>
            <h4 class="font-medium text-primary-600 dark:text-primary-400">
                {{ $booking->court->name ?? 'Court #' . $booking->court_id }}
            </h4>
            <div class="flex flex-wrap items-center gap-1 text-sm text-white-600 dark:text-white-300">
                <span>{{ $booking->date->format('D, j M Y') }}</span>
                <span>•</span>
                <span class="font-medium">
                    {{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}
                </span>
                <span class="text-xs text-white-500 dark:text-white-400 ml-1">
                    ({{ $duration }} hour{{ $duration > 1 ? 's' : '' }})
                </span>
            </div>
            @if ($booking->total_price > 0)
                <div class="mt-1 text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Rp {{ number_format($booking->total_price, 0, ',', '.') }}
                </div>
            @else
                <div class="mt-1 text-sm font-medium text-red-500 dark:text-red-400">
                    Price not available
                </div>
            @endif
        </div>

        <div class="flex flex-col items-end gap-1">
            <button @click="open = !open" class="group transition-colors duration-200 ease-in-out"
                title="Toggle Slot Details">
                <template x-if="open">
                    <x-filament::icon icon="heroicon-o-eye-slash"
                        class="w-5 h-5 text-gray-400 group-hover:text-danger-500" />
                </template>
                <template x-if="!open">
                    <x-filament::icon icon="heroicon-o-eye" class="w-5 h-5 text-gray-400 group-hover:text-sky-500" />
                </template>
            </button>
        </div>
    </div>

    <div x-show="open" x-collapse class="mt-4">
        <div class="text-sm text-gray-600 dark:text-gray-300 font-medium mb-2">
            Slot Details
        </div>

        @foreach ($grouped as $ruleName => $group)
            <div class="mb-2">
                <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">
                    {{ $ruleName }} — Rp{{ number_format($group['price'], 0, ',', '.') }} ×
                    {{ $group['slots']->count() }}
                </div>

                <ul
                    class="divide-y divide-gray-200 dark:divide-gray-600 rounded-md border border-gray-200 dark:border-gray-700 overflow-hidden">
                    @foreach ($group['slots'] as $slot)
                        <li
                            class="flex items-center gap-4 px-4 py-2 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                            <div class="w-32 font-mono text-sm text-gray-800 dark:text-gray-200">
                                {{ $slot->start_at->format('H:i') }} - {{ $slot->end_at->format('H:i') }}
                            </div>

                            <div class="w-28 text-sm text-right text-gray-700 dark:text-gray-300">
                                Rp{{ number_format($slot->price, 0, ',', '.') }}
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
</div>
