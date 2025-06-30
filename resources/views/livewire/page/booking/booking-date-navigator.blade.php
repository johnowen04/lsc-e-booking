<div class="booking-date-navigator">
    <!-- Date Range Controls -->
    <div class="range-controls-wrapper {{ $this->isDateRangeControlHidden ? 'hidden' : '' }}">
        <div class="range-controls">
            <x-filament::button wire:click="previousRange" :disabled="$this->isPreviousDisabled" :color="'rose'" size="sm">
                <span class="flex items-center">
                    <x-filament::icon icon="heroicon-o-arrow-left" class="w-4 h-4 me-1" />
                    Previous Week
                </span>
            </x-filament::button>

            <span class="date-range-label">
                {{ $this->formattedRange }}
            </span>

            <x-filament::button wire:click="nextRange" :disabled="$this->isNextDisabled" :color="'rose'" size="sm">
                <span class="flex items-center">
                    Next Week
                    <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4 me-1" />
                </span>
            </x-filament::button>
        </div>
    </div>

    <!-- Quick Date Picker + Today Button -->
    <div class="quick-picker">
        <input type="date" wire:model.live.debounce.200ms="quickPickedDate"
            min="{{ $this->minDate->toDateString() }}"
            @if ($this->maxDate) max="{{ $this->maxDate->toDateString() }}" @endif class="date-input" />

        <x-filament::button wire:click="goToToday" color="gray" :disabled="$this->isTodayDisabled">
            <div class="flex items-center gap-1">
                <x-filament::icon icon="heroicon-o-arrow-down-circle" class="w-4 h-4" />
                <span>Today</span>
            </div>
        </x-filament::button>
    </div>

    <!-- Date Tabs -->
    <div class="tab-controls">
        @foreach ($this->formattedTabDates as $tab)
            <x-filament::button :color="$tab['isActive'] ? 'rose' : 'gray'" wire:click="selectTab('{{ $tab['date'] }}')" class="tab-button">
                {{ $tab['label'] }}
            </x-filament::button>
        @endforeach
    </div>
</div>
