@props([
    'rows' => [],
    'courts' => [],
    'selectedDate' => null,
    'selectedCourtId' => null,
    'selectedStartHour' => null,
    'hoverHour' => null,
])

@if (count($rows))
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
                    @foreach ($rows as $row)
                        <tr>
                            <td class="slot-time">{{ $row->time }}</td>

                            @foreach ($courts as $court)
                                @php
                                    /** @var \App\ViewModels\Slot\SlotCellViewModel|null $cell */
                                    $cell = $row->cells[$court->id] ?? null;
                                @endphp

                                <x-booking.slot.cell :cell="$cell" :selected-court-id="$selectedCourtId" :selected-start-hour="$selectedStartHour" />
                            @endforeach
                        </tr>
                    @endforeach

                </tbody>
            </table>
        </div>
    </div>
@else
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 rounded shadow-sm text-center">
        ⚠️ No schedule has been generated for <strong>{{ $selectedDate }}</strong>.
    </div>
@endif
