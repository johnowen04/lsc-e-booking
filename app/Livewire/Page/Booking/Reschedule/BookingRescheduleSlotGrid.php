<?php

namespace App\Livewire\Page\Booking\Reschedule;

use App\Models\Booking;
use App\Models\Court;
use App\Models\CourtScheduleSlot;
use App\Traits\InteractsWithBookingCart;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Modelable;
use Livewire\Component;

class BookingRescheduleSlotGrid extends Component
{
    use InteractsWithBookingCart;

    #[Modelable]
    public string $selectedDate;
    public $courts;
    public $slots = [];

    public $selectedCourtId = null;
    public $selectedStartHour = null;
    public $hoverHour = null;

    public Booking $originalBooking;
    public array $originalSlotIds = [];
    public array $originalPricingRuleIds = [];

    protected function getCartSessionKey(): string
    {
        return 'booking_cart_reschedule';
    }

    public function mount(Booking $originalBooking)
    {
        $this->originalBooking = $originalBooking->load('slots.pricingRule'); // 👈 eager-load here

        $this->originalSlotIds = $this->originalBooking->slots->map(function ($slot) {
            return $slot->date . ':' . $slot->court_id . ':' . Carbon::parse($slot->start_at)->hour;
        })->toArray();

        $this->originalPricingRuleIds = $this->originalBooking->slots
            ->pluck('pricingRule.id') // 👈 assumes hasOne pricingRule relationship
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->courts = Court::isActive()->orderBy('name')->get();
        $this->slots = $this->getCachedSlots();
        // $this->slots = $this->generateSlotsArray();
    }

    public function updatedSelectedDate()
    {
        $this->slots = $this->getCachedSlots();
        // $this->slots = $this->generateSlotsArray();
    }

    public function hasGeneratedSchedule(): bool
    {
        return CourtScheduleSlot::where('date', $this->selectedDate)->exists();
    }

    protected function getCachedSlots()
    {
        $cacheKey = "slots_{$this->selectedDate}_reschedule";
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
            ->with('pricingRule') // 👈 preload pricing rule for each slot
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
            $hour = $slotStart->hour;
            $slotKey = $slotStart->toDateString() . ':' . $court->id . ':' . $hour;

            $slot = $scheduleSlots
                ->where('court_id', $court->id)
                ->first(fn($s) => $s->start_at->equalTo($slotStart));

            // Default status from DB
            $status = match ($slot?->status) {
                'confirmed' => 'booked',
                'held' => 'held',
                default => 'available',
            };

            $price = $slot?->price ?? 0;
            $pricingRuleId = $slot?->pricingRule?->id;

            $isOriginalSlot = in_array($slotKey, $this->originalSlotIds);
            $isPricingMatch = in_array($pricingRuleId, $this->originalPricingRuleIds);

            // ✅ Always free up original slots
            if ($isOriginalSlot) {
                $status = 'available';
            }

            // ✅ Enable if it's an original slot OR pricing rule matches
            $isBookable = $status === 'available'
                && $slotStart->gte($bookingCutoff)
                && ($isOriginalSlot || $isPricingMatch);

            return [$court->id => [
                'price' => $price,
                'status' => $status,
                'is_bookable' => $isBookable,
                'is_original' => $isOriginalSlot,
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
        if ($this->getCartTotalPrice() >= $this->originalBooking->total_price) {
            return $this->warnAndReset("You've already selected slots equal to the original booking value.");
        }

        if ($this->isSlotInCart($this->selectedDate, $courtId, $hour)) {
            return $this->warnAndReset('This slot is already in your cart');
        }

        if ($this->isSlotBooked($courtId, $hour)) {
            return $this->warnAndReset('This slot is already booked');
        }

        if ($this->selectedCourtId === $courtId) {
            $court = $this->courts->firstWhere('id', $courtId);

            if ($this->selectedStartHour === $hour) {
                $price = $this->getSelectionTotalPrice($courtId, $hour, $hour);
                if ($price > $this->originalBooking->total_price) {
                    return $this->warnAndReset("Total price must match original booking price");
                }

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

                $price = $this->getSelectionTotalPrice($courtId, $this->selectedStartHour, $hour);
                if ($price > $this->originalBooking->total_price) {
                    return $this->warnAndReset("Selected slots must match original booking price (Rp " . number_format($this->originalBooking->total_price, 0, ',', '.') . ")");
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

    protected function getSelectionTotalPrice(int $courtId, int $startHour, int $endHour): float
    {
        $total = 0;

        for ($h = $startHour; $h <= $endHour; $h++) {
            foreach ($this->slots as $slot) {
                if ($slot['hour'] === $h && isset($slot['slots'][$courtId]['price'])) {
                    $total += $slot['slots'][$courtId]['price'];
                    break;
                }
            }
        }

        return $total;
    }

    protected function getCartTotalPrice(): float
    {
        $total = 0;

        foreach ($this->getCart() as $slot) {
            foreach ($this->slots as $row) {
                if ($row['hour'] === $slot['hour'] && isset($row['slots'][$slot['court_id']]['price'])) {
                    $total += $row['slots'][$slot['court_id']]['price'];
                    break;
                }
            }
        }

        return $total;
    }

    public function render()
    {
        return view('livewire.page.booking.reschedule.booking-reschedule-slot-grid');
    }
}
