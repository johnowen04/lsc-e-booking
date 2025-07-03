<?php

namespace App\Livewire\Page\Booking;

use App\Models\Court;
use App\Models\CourtScheduleSlot;
use App\Traits\InteractsWithBookingCart;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class BookingSlotGrid extends Component
{
    use InteractsWithBookingCart;

    #[Modelable]
    public string $selectedDate;
    public $courts;
    public $slots = [];

    public $selectedCourtId = null;
    public $selectedStartHour = null;
    public $hoverHour = null;

    public function mount()
    {
        $this->courts = Court::isActive()->orderBy('name')->get();
        $this->slots = $this->getCachedSlots();
    }

    public function updatedSelectedDate()
    {
        $this->slots = $this->getCachedSlots();
    }

    public function hasGeneratedSchedule(): bool
    {
        return CourtScheduleSlot::where('date', $this->selectedDate)->exists();
    }

    protected function getCachedSlots()
    {
        $cacheKey = "slots_{$this->selectedDate}";
        $ttl = $this->selectedDate === today()->toDateString()
            ? now()->addSeconds(30)
            : now()->addMinutes(2);

        return Cache::remember($cacheKey, $ttl, fn() => $this->generateSlotsArray());
    }

    protected function generateSlotsArray(): Collection
    {
        $startHour = 8;
        $endHour = 22;

        $bookingDate = Carbon::parse($this->selectedDate);
        $bookingCutoff = now()->subHour();

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
            $bookingCutoff
        ) {
            $slotStart = $bookingDate->copy()->setTime($hour, 0);
            $slotEnd = $slotStart->copy()->addHour();

            return [
                'time' => "{$slotStart->format('H:i')} - {$slotEnd->format('H:i')}",
                'hour' => $hour,
                'slots' => $this->generateCourtSlots($slotStart, $scheduleSlots, $bookingCutoff),
            ];
        })->values();
    }

    protected function generateCourtSlots(
        Carbon $slotStart,
        $scheduleSlots,
        Carbon $bookingCutoff
    ): array {
        return $this->courts->mapWithKeys(function ($court) use (
            $slotStart,
            $scheduleSlots,
            $bookingCutoff
        ) {
            $slot = $scheduleSlots
                ->where('court_id', $court->id)
                ->first(fn($s) => $s->start_at->equalTo($slotStart));

            $status = match ($slot?->status) {
                'confirmed' => 'booked',
                'held' => 'held',
                default => 'available',
            };

            $price = $slot?->price ?? 0;

            return [$court->id => [
                'price' => $price,
                'status' => $status,
                'is_bookable' => $status === 'available' && $slotStart->gte($bookingCutoff),
            ]];
        })->toArray();
    }

    public function setHoverHour($hour)
    {
        if ($this->selectedCourtId !== null && $this->selectedStartHour !== null) {
            if ($hour < $this->selectedStartHour) {
                $this->hoverHour = $this->selectedStartHour;
                return;
            }

            $min = $this->selectedStartHour;
            $max = $hour;

            for ($h = $min; $h <= $max; $h++) {
                if (
                    $this->isSlotInCart($this->selectedDate, $this->selectedCourtId, $h) ||
                    $this->isSlotBooked($this->selectedCourtId, $h)
                ) {
                    $this->hoverHour = $h - 1;
                    return;
                }
            }

            $this->hoverHour = $hour;
            return;
        }

        $this->hoverHour = $hour;
    }

    public function isSlotBooked($courtId, $hour): bool
    {
        foreach ($this->slots as $slot) {
            if ($slot['hour'] === $hour && isset($slot['slots'][$courtId]['status'])) {
                return in_array($slot['slots'][$courtId]['status'], ['booked', 'held']);
            }
        }
        return false;
    }

    public function isSlotInCart($date, $courtId, $hour): bool
    {
        return collect($this->getCart())->contains(
            fn($slot) =>
            $slot['date'] === $date &&
                $slot['court_id'] === $courtId &&
                $slot['hour'] === $hour
        );
    }

    public function selectSlot($courtId, $hour)
    {
        if ($this->isSlotInCart($this->selectedDate, $courtId, $hour)) {
            return $this->warnAndReset('This slot is already in your cart');
        }

        if ($this->isSlotBooked($courtId, $hour)) {
            return $this->warnAndReset('This slot is already booked');
        }

        if ($this->selectedCourtId === $courtId) {
            $court = $this->courts->firstWhere('id', $courtId);

            if ($this->selectedStartHour === $hour) {
                $this->addSlotsToCart($this->selectedDate, $courtId, $court->name, $hour);
                $this->resetSelection();
                return;
            }

            if ($this->selectedStartHour !== null) {
                if ($hour < $this->selectedStartHour) {
                    return $this->warnAndReset('Please select slots in forward order');
                }

                if ($this->selectionHasConflict($courtId, $this->selectedStartHour, $hour)) {
                    return $this->warnAndReset('Your selection crosses unavailable slots');
                }

                $this->addSlotsToCart($this->selectedDate, $courtId, $court->name, $this->selectedStartHour, $hour);
                $this->resetSelection();
                return;
            }
        }

        $this->selectedCourtId = $courtId;
        $this->selectedStartHour = $hour;
        $this->hoverHour = $hour;
    }

    public function isSelected($courtId, $hour): bool
    {
        if ($this->selectedCourtId !== $courtId) {
            return false;
        }

        if ($this->selectedStartHour === null || $this->hoverHour === null) {
            return false;
        }

        $start = min($this->selectedStartHour, $this->hoverHour);
        $end = max($this->selectedStartHour, $this->hoverHour);

        return $hour >= $start && $hour <= $end;
    }

    public function isEndSelection($courtId, $hour): bool
    {
        if ($this->selectedCourtId !== $courtId) {
            return false;
        }

        if ($this->selectedStartHour === null || $this->hoverHour === null) {
            return false;
        }

        return $hour === $this->hoverHour && $hour !== $this->selectedStartHour;
    }

    public function clearSelection()
    {
        $this->resetSelection();
    }

    protected function resetSelection()
    {
        $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
    }

    protected function selectionHasConflict($courtId, $startHour, $endHour): bool
    {
        for ($h = $startHour; $h <= $endHour; $h++) {
            if ($this->isSlotInCart($this->selectedDate, $courtId, $h) || $this->isSlotBooked($courtId, $h)) {
                return true;
            }
        }
        return false;
    }

    protected function warnAndReset(string $message)
    {
        Notification::make()->title($message)->warning()->send();
        $this->resetSelection();
    }

    public function render()
    {
        return view('livewire.page.booking.booking-slot-grid');
    }
}
