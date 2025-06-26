<?php

namespace App\Livewire;

use App\Traits\InteractsWithBookingCart;
use Livewire\Component;
use App\Models\BookingSlot;
use App\Models\Court;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class BookingSlotGrid extends Component
{
    use InteractsWithBookingCart;

    public $selectedDate;
    public $courts;
    public $slots = [];

    public $selectedCourtId = null;
    public $selectedStartHour = null;
    public $hoverHour = null;

    public $baseDate;
    public $daysShown = 7;
    public $tabDates = [];
    public $activeTabDate;
    public $quickPickedDate;

    public function mount()
    {
        $this->selectedDate = now()->toDateString();
        $this->courts = Court::isActive()->orderBy('name')->get();
        $this->generateSlots();

        $this->baseDate = Carbon::parse(now());
        $this->updateTabDates();

        if (!Session::has('booking_cart')) {
            Session::put('booking_cart', []);
        }
    }

    public function updatedSelectedDate()
    {
        $cacheKey = "slots_{$this->selectedDate}";
        $ttl = $this->selectedDate === today()->toDateString()
            ? now()->addSeconds(30) //ttl
            : now()->addMinutes(2); //ttl

        if (Cache::has($cacheKey)) {
            $this->slots = Cache::pull($cacheKey);
        } else {
            $this->generateSlots();
            Cache::put($cacheKey, $this->slots, $ttl); //ttl
        }
    }

    public function selectTab($date)
    {
        $this->activeTabDate = $date;
        $this->selectedDate = $date;
        $this->quickPickedDate = $date;
        $this->updatedSelectedDate();
    }

    public function updateTabDates()
    {
        $this->tabDates = collect(range(0, $this->daysShown - 1))
            ->map(fn($i) => $this->baseDate->copy()->addDays($i)->toDateString())
            ->toArray();

        $this->activeTabDate = $this->tabDates[0];
        $this->selectedDate = $this->activeTabDate;
        $this->quickPickedDate = $this->selectedDate;

        $this->updatedSelectedDate();
    }

    public function previousRange()
    {
        $this->baseDate = $this->baseDate->subDays($this->daysShown);
        $this->updateTabDates();
    }

    public function nextRange()
    {
        $this->baseDate = $this->baseDate->addDays($this->daysShown);
        $this->updateTabDates();
    }

    public function updatedQuickPickedDate($value)
    {
        $picked = Carbon::parse($value);

        // Set base date to start of week containing picked date (or adjust based on your logic)
        $this->baseDate = $picked->copy()->startOfDay();
        $this->updateTabDates();

        // Set active tab to picked date (if it exists in the new range)
        if (in_array($picked->toDateString(), $this->tabDates)) {
            $this->selectTab($picked->toDateString());
        } else {
            // Optional: select closest available day in new range
            $this->activeTabDate = $this->tabDates[0];
            $this->selectedDate = $this->tabDates[0];
            $this->updatedSelectedDate();
        }
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
                    $this->isSlotInCart($this->selectedCourtId, $h) ||
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

    public function hasBookedSlotBetween($courtId, $startHour, $endHour)
    {
        for ($hour = $startHour; $hour <= $endHour; $hour++) {
            if ($this->isSlotBooked($courtId, $hour)) {
                return true;
            }
        }
        return false;
    }

    public function isSelected($courtId, $hour)
    {
        if ($courtId !== $this->selectedCourtId) return false;
        if ($this->selectedStartHour === null) return false;

        $min = $this->selectedStartHour;
        $max = $this->hoverHour !== null ? $this->hoverHour : $this->selectedStartHour;

        if ($max < $min) {
            $max = $min;
        }

        if ($this->hasBookedSlotBetween($courtId, $min, $max)) {
            for ($h = $min + 1; $h <= $max; $h++) {
                if ($this->isSlotBooked($courtId, $h)) {
                    return $hour >= $min && $hour < $h;
                }
            }
        }

        return $hour >= $min && $hour <= $max;
    }

    public function isEndSelection($courtId, $hour)
    {
        if ($courtId !== $this->selectedCourtId) return false;
        if ($this->selectedStartHour === null) return false;
        if ($this->selectedStartHour === $hour) return false;

        $min = $this->selectedStartHour;
        $max = $this->hoverHour !== null ? $this->hoverHour : $this->selectedStartHour;

        if ($max < $min) {
            return false;
        }

        if ($this->hasBookedSlotBetween($courtId, $min, $max)) {
            for ($h = $min + 1; $h <= $max; $h++) {
                if ($this->isSlotBooked($courtId, $h)) {
                    return $hour === ($h - 1);
                }
            }
        }

        return $hour === $max;
    }

    public function cancelSelection()
    {
        $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
    }

    public function isSlotBooked($courtId, $hour)
    {
        foreach ($this->slots as $slot) {
            if ($slot['hour'] === $hour && isset($slot['slots'][$courtId])) {
                return $slot['slots'][$courtId] === 'booked' || $slot['slots'][$courtId] === 'held';
            }
        }
        return false;
    }

    public function generateSlots()
    {
        $startHour = 8;
        $endHour = 22;

        $start = Carbon::parse("{$this->selectedDate} {$startHour}:00");
        $end = Carbon::parse("{$this->selectedDate} {$endHour}:00");

        $bookingSlots = BookingSlot::query()
            ->whereIn('court_id', $this->courts->pluck('id'))
            ->where('slot_start', '<', $end)
            ->where('slot_end', '>', $start)
            ->whereIn('status', ['confirmed', 'held'])
            ->get();

        $this->slots = collect(range($startHour, $endHour - 1))->map(function ($hour) use ($bookingSlots) {
            $slotStart = Carbon::parse("{$this->selectedDate} {$hour}:00");
            $slotEnd = $slotStart->copy()->addHour();

            $row = [
                'time' => "{$slotStart->format('H:i')} - {$slotEnd->format('H:i')}",
                'slots' => [],
                'hour' => $hour,
            ];

            foreach ($this->courts as $court) {
                $overlappingBooking = $bookingSlots
                    ->where('court_id', $court->id)
                    ->first(function ($slot) use ($slotStart, $slotEnd) {
                        return $slot->slot_start < $slotEnd && $slot->slot_end > $slotStart;
                    });

                $row['slots'][$court->id] = match ($overlappingBooking->status ?? null) {
                    'confirmed' => 'booked',
                    'held' => 'held',
                    default => 'available',
                };
            }

            return $row;
        })->values();
    }

    public function selectSlot($courtId, $hour)
    {
        if ($this->isSlotInCart($courtId, $hour)) {
            return $this->warnAndReset('This slot is already in your cart');
        }

        if ($this->isSlotBooked($courtId, $hour)) {
            return $this->warnAndReset('This slot is already booked');
        }

        if ($this->selectedCourtId === $courtId) {
            $court = $this->courts->firstWhere('id', $courtId);

            if ($this->selectedStartHour === $hour) {
                $this->addSlotToCartFlexible($this->selectedDate, $courtId, $court->name, $hour);
                $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
                return;
            }

            if ($this->selectedStartHour !== null) {
                if ($hour < $this->selectedStartHour) {
                    return $this->warnAndReset('Please select slots in forward order');
                }

                if ($this->selectionHasConflict($courtId, $this->selectedStartHour, $hour)) {
                    return $this->warnAndReset('Your selection crosses unavailable slots');
                }

                $this->addSlotToCartFlexible($this->selectedDate, $courtId, $court->name, $this->selectedStartHour, $hour);
                $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
                return;
            }
        }

        $this->selectedCourtId = $courtId;
        $this->selectedStartHour = $hour;
        $this->hoverHour = $hour;
    }

    protected function warnAndReset(string $message)
    {
        Notification::make()->title($message)->warning()->send();
        $this->reset(['selectedCourtId', 'selectedStartHour', 'hoverHour']);
    }

    protected function selectionHasConflict($courtId, $startHour, $endHour): bool
    {
        for ($h = $startHour; $h <= $endHour; $h++) {
            if ($this->isSlotInCart($courtId, $h) || $this->isSlotBooked($courtId, $h)) {
                return true;
            }
        }
        return false;
    }

    protected function getListeners()
    {
        return [
            'bookingCreated' => 'cancelSelection',
            'cartCleared' => '$refresh',
            'cartItemRemoved' => '$refresh',
        ];
    }

    public function render()
    {
        return view('livewire.booking-slot-grid');
    }
}
