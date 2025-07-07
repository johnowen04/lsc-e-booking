<?php

namespace App\Livewire\Page\Booking;

use App\DTOs\Slot\SlotData;
use App\Models\Court;
use App\Models\CourtScheduleSlot;
use App\Traits\InteractsWithBookingCart;
use App\Traits\InteractsWithSlotSelection;
use App\ViewModels\Slot\Factories\SlotCellFactory;
use App\ViewModels\Slot\SlotRowViewModel;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class BookingSlotGrid extends Component
{
    use InteractsWithBookingCart;
    use InteractsWithSlotSelection;

    #[Modelable]
    public string $selectedDate;
    public Collection $courts;
    public array $slots = [];

    public ?int $selectedCourtId = null;
    public ?int $selectedStartHour = null;
    public ?int $hoverHour = null;

    public function mount()
    {
        $this->courts = Court::isActive()->orderBy('name')->get();
        $this->slots = $this->getCachedSlots();
    }

    public function hasGeneratedSchedule(): bool
    {
        return CourtScheduleSlot::where('date', $this->selectedDate)->exists();
    }

    public function updatedSelectedDate()
    {
        $this->slots = $this->getCachedSlots();
    }

    public function getRows(): Collection
    {
        return collect($this->slots)->map(function (array $row) {
            $cells = collect($this->courts)->mapWithKeys(function ($court) use ($row) {
                /** @var \App\Models\Court $court */
                $slotData = SlotData::fromArray($row['cells'][$court->id] ?? []);

                if (! $slotData instanceof SlotData) {
                    return [$court->id => null];
                }

                $inCart = $this->isSlotInCart($this->selectedDate, $court->id, $slotData->hour);

                return [
                    $court->id => SlotCellFactory::fromSlotData(
                        slotData: $slotData,
                        selectedCourtId: $this->selectedCourtId ?? 1,
                        selectedStartHour: $this->selectedStartHour ?? 1,
                        hoverHour: $this->hoverHour ?? 1,
                        inCart: $inCart,
                    )
                ];
            });

            return new SlotRowViewModel(
                time: $row['time'],
                hour: $row['hour'],
                cells: $cells
            );
        });
    }

    public function setHoverHour($hour): void
    {
        if ($this->selectedCourtId !== null && $this->selectedStartHour !== null) {
            if ($hour < $this->selectedStartHour) {
                $this->hoverHour = $this->selectedStartHour;
                return;
            }

            for ($h = $this->selectedStartHour; $h <= $hour; $h++) {
                if (
                    $this->isSlotInCart($this->selectedDate, $this->selectedCourtId, $h) ||
                    $this->isSlotBooked($this->slots, $this->selectedCourtId, $h)
                ) {
                    $this->hoverHour = $h - 1;
                    return;
                }
            }
        }

        $this->hoverHour = $hour;
    }

    public function selectSlot($courtId, $hour)
    {
        if ($this->isSlotInCart($this->selectedDate, $courtId, $hour)) {
            return $this->warnAndReset('This slot is already in your cart');
        }

        if ($this->isSlotBooked($this->slots, $courtId, $hour)) {
            return $this->warnAndReset('This slot is already booked');
        }

        if ($this->selectedCourtId === $courtId) {
            $court = $this->courts->firstWhere('id', $courtId);

            if ($this->selectedStartHour === $hour) {
                $this->addSlotsToCart($this->selectedDate, $courtId, $court->name, $hour);
                return $this->resetSelection();
            }

            if ($this->selectedStartHour !== null) {
                if ($hour < $this->selectedStartHour) {
                    return $this->warnAndReset('Please select slots in forward order');
                }

                if ($this->selectionHasConflict($this->slots, $courtId, $this->selectedStartHour, $hour)) {
                    return $this->warnAndReset('Your selection crosses unavailable slots');
                }

                $this->addSlotsToCart($this->selectedDate, $courtId, $court->name, $this->selectedStartHour, $hour);
                return $this->resetSelection();
            }
        }

        $this->selectedCourtId = $courtId;
        $this->selectedStartHour = $hour;
        $this->hoverHour = $hour;
    }

    public function clearSelection(): void
    {
        $this->resetSelection();
    }

    protected function getCachedSlots(): array
    {
        $cacheKey = "slots_{$this->selectedDate}";
        $ttl = $this->selectedDate === today()->toDateString()
            ? now()->addSeconds(30)
            : now()->addMinutes(2);

        return Cache::remember($cacheKey, $ttl, fn() => $this->generateSlotsArray());
    }

    protected function generateSlotsArray(): array
    {
        $startHour = 8;
        $endHour = 22;

        $bookingDate = Carbon::parse($this->selectedDate);
        $cutoff = now()->subHour();

        $start = $bookingDate->copy()->setTime($startHour, 0);
        $end = $bookingDate->copy()->setTime($endHour, 0);

        $courtIds = $this->courts->pluck('id');

        $scheduleSlots = CourtScheduleSlot::query()
            ->whereIn('court_id', $courtIds)
            ->whereDate('date', $this->selectedDate)
            ->whereBetween('start_at', [$start, $end])
            ->get();

        return collect(range($startHour, $endHour - 1))->map(function ($hour) use (
            $scheduleSlots,
            $bookingDate,
            $cutoff
        ) {
            $slotStart = $bookingDate->copy()->setTime($hour, 0);
            $slotEnd = $slotStart->copy()->addHour();

            return [
                'time' => "{$slotStart->format('H:i')} - {$slotEnd->format('H:i')}",
                'hour' => $hour,
                'cells' => $this->generateCourtSlotData($slotStart, $scheduleSlots, $cutoff),
            ];
        })->values()->toArray();
    }

    protected function generateCourtSlotData(Carbon $slotStart, Collection $scheduleSlots, Carbon $cutoff): Collection
    {
        return $this->courts->mapWithKeys(function (Court $court) use ($slotStart, $scheduleSlots, $cutoff) {
            $slot = $scheduleSlots
                ->where('court_id', $court->id)
                ->first(fn($s) => $s->start_at->equalTo($slotStart));

            return [$court->id => SlotData::fromScheduleSlot($court, $slotStart, $slot, $cutoff)->toArray()];
        });
    }

    protected function warnAndReset(string $message): void
    {
        \Filament\Notifications\Notification::make()
            ->title($message)
            ->warning()
            ->send();

        $this->resetSelection();
    }

    protected function resetSelection(): void
    {
        $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
    }

    public function render()
    {
        return view('livewire.page.booking.booking-slot-grid', [
            'rows' => $this->getRows(),
            'courts' => $this->courts,
        ]);
    }
}
