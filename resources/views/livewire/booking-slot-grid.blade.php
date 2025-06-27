@php
    $cart = session('booking_cart', []);
@endphp
<div class="space-y-4" x-data="{
    init() {
        window.addEventListener('click', e => {
            if (!this.$el.contains(e.target) &&
                @entangle('selectedCourtId').defer !== null &&
                !e.target.closest('.booking-form-drawer')) {
                @this.call('clearSelection');
            }
        });

        window.addEventListener('bookingCreated', () => {
            @this.call('clearSelection');
        });
    }
}">
    <div class="overflow-auto border rounded-md dark:border-gray-700">
        <table class="w-full table-auto text-sm text-center border-collapse">
            <thead class="bg-gray-100 dark:bg-gray-800 text-black dark:text-gray-200">
                <tr>
                    <th class="p-3 border-b dark:border-gray-700 text-center">Time</th>
                    @foreach ($courts as $court)
                        <th class="p-3 border-b dark:border-gray-700">🏟 {{ $court->name }}</th>
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
                                $inCart = collect($cart)->contains(
                                    fn($slot) => $slot['date'] === $selectedDate &&
                                        $slot['court_id'] === $court->id &&
                                        $slot['hour'] === $row['hour'],
                                );
                                $isStart = $selectedCourtId === $court->id && $selectedStartHour === $row['hour'];
                                $isInSelection =
                                    $isStart ||
                                    ($hoverHour !== null &&
                                        $court->id === $selectedCourtId &&
                                        $row['hour'] >= $selectedStartHour &&
                                        $row['hour'] <= $hoverHour);
                                $isEnd = $isInSelection && $row['hour'] === $hoverHour;
                                $isDisabled = $status !== 'available' || $inCart;
                            @endphp

                            <td class="p-3">
                                @if ($status === 'available')
                                    @if ($isDisabled)
                                        <x-filament::button :disabled="true" :color="$inCart ? 'info' : 'secondary'">
                                            {{ $inCart ? 'In Cart' : 'Available' }}
                                        </x-filament::button>
                                    @else
                                        <x-filament::button
                                            wire:click="selectSlot({{ $court->id }}, {{ $row['hour'] }})"
                                            wire:mouseover="setHoverHour({{ $row['hour'] }})" size="sm"
                                            :color="$inCart ? 'info' : ($isInSelection ? 'indigo' : 'success')" class="!text-white font-semibold">
                                            @if ($isStart)
                                                Start
                                            @elseif ($isEnd)
                                                End
                                            @elseif ($inCart)
                                                In Cart
                                            @elseif ($isInSelection)
                                                Picked
                                            @else
                                                Book
                                            @endif
                                        </x-filament::button>
                                    @endif
                                @elseif ($status === 'held')
                                    <x-filament::button :disabled="true" color="warning">
                                        Held
                                    </x-filament::button>
                                @elseif ($status === 'booked')
                                    <x-filament::button :disabled="true" color="danger">
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
