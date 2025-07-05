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
    @if ($this->hasGeneratedSchedule())
        <div class="booking-slot-grid">
            <div class="table-container">
                <table class="slot-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            @foreach ($courts as $court)
                                <th>🏟 {{ $court->name }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($slots as $row)
                            <tr>
                                <td class="slot-time">
                                    {{ $row['time'] }}
                                </td>

                                @foreach ($courts as $court)
                                    @php
                                        $slotData = $row['slots'][$court->id];
                                        $status = $slotData['status'];
                                        $isBookable = $slotData['is_bookable'] ?? false;
                                        $isOriginal = $slotData['is_original'] ?? false;
                                        $inCart = collect($this->getCart())->contains(
                                            fn($slot) => $slot['date'] === $selectedDate &&
                                                $slot['court_id'] === $court->id &&
                                                $slot['hour'] === $row['hour'],
                                        );
                                        $isStart =
                                            $selectedCourtId === $court->id && $selectedStartHour === $row['hour'];
                                        $isInSelection =
                                            $isStart ||
                                            ($hoverHour !== null &&
                                                $court->id === $selectedCourtId &&
                                                $row['hour'] >= $selectedStartHour &&
                                                $row['hour'] <= $hoverHour);
                                        $isEnd = $isInSelection && $row['hour'] === $hoverHour;
                                        $isCartFull = $this->getCartTotalPrice() >= $this->originalBooking->total_price;
                                        $isDisabled = $status !== 'available' || $inCart || !$isBookable || $isCartFull;
                                    @endphp

                                    <td class="group booking-slot-cell
                                @if ($status === 'booked') booked
                                @elseif ($status === 'held') held
                                @elseif ($isDisabled) disabled
                                @elseif ($isOriginal) original available
                                @else available @endif"
                                        @if (!$isDisabled && $status === 'available') wire:click="selectSlot({{ $court->id }}, {{ $row['hour'] }})"
                                    wire:mouseover="setHoverHour({{ $row['hour'] }})" @endif>
                                        <div class="booking-slot-content">
                                            @if ($status === 'booked')
                                                <span class="slot-label">Booked</span>
                                            @elseif ($status === 'held')
                                                <span class="slot-label">Held</span>
                                            @elseif ($isDisabled)
                                                <span class="slot-label {{ $inCart ? 'in-cart' : '' }}">
                                                    {{ $inCart ? 'In Cart' : 'Available' }}
                                                </span>
                                            @else
                                                <span
                                                    class="slot-label
                                                    @if ($inCart) in-cart
                                                    @elseif($isInSelection) in-selection
                                                    @elseif($isStart || $isEnd) is-range-endpoint @endif">
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

                                                    @if ($isOriginal)
                                                        <span
                                                            class="ml-1 w-2 h-2 inline-block bg-blue-500 rounded-sm"></span>
                                                    @endif
                                                </span>


                                                {{-- @if ($isOriginal)
                                                    <span
                                                        class="original-booking-tag text-[10px] text-blue-500 font-semibold block leading-none mt-0.5">
                                                        Originally booked
                                                    </span>
                                                @endif --}}
                                            @endif

                                            @if (!empty($slotData['price']))
                                                <span class="slot-price">
                                                    Rp {{ number_format($slotData['price']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded shadow-sm text-center">
            ⚠️ No schedule has been generated for <strong>{{ $selectedDate }}</strong>.<br>
            Please select another date or contact the admin.
        </div>
    @endif
</div>
