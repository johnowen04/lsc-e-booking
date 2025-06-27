<div>
    <!-- Date Range Controls -->
    <div class="flex justify-between mb-2 items-center">
        <x-filament::button wire:click="previousRange" :disabled="$this->isPreviousDisabled" :color="'rose'" size="sm">
            <span class="flex items-center">
                <x-filament::icon icon="heroicon-o-arrow-left" class="w-4 h-4 me-1" />
                Previous Week
            </span>
        </x-filament::button>

        <span class="text-center font-medium text-gray-900 dark:text-white">
            {{ $this->formattedRange }}
        </span>

        <x-filament::button wire:click="nextRange" :disabled="$this->isNextDisabled" :color="'rose'" size="sm">
            <span class="flex items-center">
                Next Week
                <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4 me-1" />
            </span>
        </x-filament::button>
    </div>

    <!-- Quick Date Picker + Today Button -->
    <div class="flex justify-center items-center gap-2 mb-4">
        <input type="date" wire:model.live="quickPickedDate" min="{{ $this->minDate->toDateString() }}"
            @if ($this->maxDate) max="{{ $this->maxDate->toDateString() }}" @endif
            class="p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />

        <x-filament::button wire:click="goToToday" color="gray" :disabled="$this->isTodayDisabled">
            Today
        </x-filament::button>
    </div>

    <!-- Date Tabs -->
    <div class="flex justify-center items-center mb-4 gap-2 flex-wrap">
        @foreach ($this->formattedTabDates as $tab)
            <x-filament::button :color="$tab['isActive'] ? 'rose' : 'gray'" wire:click="selectTab('{{ $tab['date'] }}')"
                class="px-4 py-2 rounded text-sm font-medium">
                {{ $tab['label'] }}
            </x-filament::button>
        @endforeach
    </div>
</div>
