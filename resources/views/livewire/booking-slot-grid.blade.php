<div class="space-y-4" x-data="{
    init() {
        window.addEventListener('click', e => {
            if (!this.$el.contains(e.target) &&
            @this.selectedCourtId !== null &&
            // Check if click is outside the booking grid AND outside the booking form drawer
                !e.target.closest('.booking-form-drawer')) {
                @this.cancelSelection();
            }
        });

        // Listen for bookingCreated event from parent component
        window.addEventListener('bookingCreated', () => {
            @this.cancelSelection();
        });
    }
}">
    @php
        use Carbon\Carbon;
    @endphp
    <!-- Date Range Controls -->
    <div class="flex justify-between mb-2 items-center">
        <x-filament::button :color="'rose'" wire:click="previousRange" size="sm">
            <span class="flex items-center">
                <x-filament::icon icon="heroicon-o-arrow-left" class="w-4 h-4 me-1" />
                Previous Week
            </span>
        </x-filament::button>

        <span class="text-center font-medium text-gray-900 dark:text-white">
            Booking For: {{ Carbon::parse($baseDate)->format('j M') }} –
            {{ Carbon::parse(end($tabDates))->format('j M Y') }}
        </span>

        <x-filament::button :color="'rose'" wire:click="nextRange" size="sm">
            <span class="flex items-center">
                Next Week
                <x-filament::icon icon="heroicon-o-arrow-right" class="w-4 h-4 me-1" />
            </span>
        </x-filament::button>
    </div>

    <!-- Date Tabs + Date Picker -->
    <div class="flex justify-between items-center mb-4 gap-4 flex-wrap">
        <!-- Date Tabs -->
        <div class="flex flex-wrap gap-2">
            @foreach ($tabDates as $date)
                <x-filament::button :color="$activeTabDate === $date ? 'rose' : 'gray'" wire:click="selectTab('{{ $date }}')"
                    class="px-4 py-2 rounded text-sm font-medium">
                    {{ \Carbon\Carbon::parse($date)->format('D, d M') }}
                </x-filament::button>
            @endforeach
        </div>

        <!-- Quick Date Picker -->
        <div class="relative">
            <input type="date" wire:model.lazy="quickPickedDate"
                class="p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500" />
        </div>
    </div>

    <!-- Slot Grid -->
    <div class="overflow-auto border rounded-md dark:border-gray-700">
        <table class="w-full table-auto text-sm text-center border-collapse">
            <thead class="bg-gray-100 dark:bg-gray-800 text-black dark:text-gray-200">
                <tr>
                    <th class="p-3 border-b dark:border-gray-700 text-center">Time</th>
                    @foreach ($courts as $court)
                        <th class="p-3 border-b dark:border-gray-700"> 🏟 {{ $court->name }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="bg-white dark:bg-gray-900">
                @foreach ($slots as $row)
                    <tr class="border-b dark:border-gray-800">
                        <td class="p-3 text-center font-medium text-black dark:text-gray-100 whitespace-nowrap">
                            {{ $row['time'] }}
                        </td>

                        @foreach ($courts as $court)
                            @php
                                $status = $row['slots'][$court->id];
                                $isStart = $selectedCourtId === $court->id && $selectedStartHour === $row['hour'];
                                $isInSelection =
                                    $this->isSelected($court->id, $row['hour']) ||
                                    $this->isSlotInCart($court->id, $row['hour']);
                                $isEnd = $this->isEndSelection($court->id, $row['hour']);
                                $isDisabled = $status !== 'available' || $this->isSlotInCart($court->id, $row['hour']);
                            @endphp

                            <td class="p-3">
                                @if ($status === 'available')
                                    @if ($isDisabled)
                                        <x-filament::button :disabled="true" :color="$this->isSlotInCart($court->id, $row['hour']) ? 'info' : 'secondary'">
                                            {{ $this->isSlotInCart($court->id, $row['hour']) ? 'In Cart' : ucfirst($status) }}
                                        </x-filament::button>
                                    @else
                                        <x-filament::button
                                            wire:click="selectSlot({{ $court->id }}, {{ $row['hour'] }})"
                                            wire:mouseover="setHoverHour({{ $row['hour'] }})" size="sm"
                                            :color="$this->isSlotInCart($court->id, $row['hour'])
                                                ? 'info'
                                                : ($isInSelection
                                                    ? 'indigo'
                                                    : 'success')" class="!text-white font-semibold">
                                            @if ($isStart)
                                                Start
                                            @elseif ($isEnd)
                                                End
                                            @elseif ($this->isSlotInCart($court->id, $row['hour']))
                                                In Cart
                                            @elseif($isInSelection)
                                                Picked
                                            @else
                                                Book
                                            @endif
                                        </x-filament::button>
                                    @endif
                                @elseif ($status === 'held')
                                    <x-filament::button :disabled="true" :color="'warning'">
                                        Held
                                    </x-filament::button>
                                @elseif ($status === 'booked')
                                    <x-filament::button :disabled="true" :color="'danger'">
                                        Booked
                                    </x-filament::button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
