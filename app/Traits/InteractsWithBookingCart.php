<?php

namespace App\Traits;

use App\DTOs\BookingCart\SelectedSlot;
use App\DTOs\BookingCart\SelectedSlotGroup;
use App\Models\Customer;
use App\Models\User;
use App\Services\BookingSlotService;
use App\Services\PricingRuleService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

trait InteractsWithBookingCart
{
    protected string $eventCartCleared = 'cartCleared';
    protected string $eventItemRemoved = 'cartItemRemoved';
    protected string $eventSlotsAdded = 'slotsAddedToCart';

    // ───── Public API ─────

    public function isSlotInCart(string $date, int $courtId, int $hour): bool
    {
        return collect($this->getCart())->contains(
            fn(SelectedSlot $slot) => $slot->date === $date && $slot->courtId === $courtId && $slot->hour === $hour
        );
    }

    public function calculateCartTotal(): int
    {
        return $this->getGroupedSlots()->sum(fn(SelectedSlotGroup $group) => $group->price);
    }

    public function addSlotsToCart(string $date, int $courtId, ?string $courtName, int $startHour, ?int $endHour = null): void
    {
        $cart = $this->getCart();
        $end = $endHour !== null && $endHour > $startHour ? $endHour : $startHour;

        for ($hour = $startHour; $hour <= $end; $hour++) {
            $cart[] = $this->buildSlotData($date, $courtId, $courtName, $hour);
        }

        $this->saveCart($cart);
        $this->fireEvent($this->eventSlotsAdded);
    }

    public function removeSlot(string $slotId): void
    {
        $filtered = collect($this->getCart())
            ->reject(fn(SelectedSlot $slot) => $slot->id === $slotId)
            ->values()
            ->all();

        $this->saveCart($filtered);
        $this->fireEvent($this->eventItemRemoved);
    }

    public function removeBookingGroup(string $groupId): void
    {
        $group = $this->getGroupedSlots()->first(
            fn(SelectedSlotGroup $group) => $group->groupId === $groupId
        );

        if (! $group) {
            logger()->warning("Group [$groupId] not found in cart");
            return;
        }

        $idsToRemove = $group->slots->pluck('id')->filter()->values()->all();

        if (empty($idsToRemove)) {
            logger()->warning("No slots to remove for group [$groupId]");
            return;
        }

        $filtered = collect($this->getCart())
            ->reject(fn(SelectedSlot $slot) => in_array($slot->id, $idsToRemove, true))
            ->values()
            ->all();

        $this->saveCart($filtered);
        $this->fireEvent($this->eventItemRemoved);
    }

    public function clearBookingCart(): void
    {
        $this->saveCart([]);
        $this->fireEvent($this->eventCartCleared);
    }

    public function checkBookingConflicts(array $excludeSlotIds = []): void
    {
        $groups = $this->getGroupedSlots();

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        foreach ($groups as $group) {
            $start = Carbon::parse("{$group->date} {$group->slots->min('hour')}:00");
            $end = Carbon::parse("{$group->date} {$group->slots->max('hour')}:00")->addHour();

            if ($this->checkSlotConflict($group->courtId, $start, $end, $excludeSlotIds)) {
                throw ValidationException::withMessages([
                    'cart' => "One or more selected slots for {$group->courtName} on {$group->date} are already booked.",
                ]);
            }
        }
    }

    public function getGroupedSlots(): Collection
    {
        return collect($this->getCart())
            ->sortBy(fn($slot) => [$slot->courtId, $slot->date, $slot->hour])
            ->groupBy(fn($slot) => "{$slot->courtId}_{$slot->date}")
            ->flatMap(fn($group) => $this->groupConsecutiveSlots($group))
            ->values();
    }

    // ───── Session / Persistence ─────

    protected function getCart(): array
    {
        return collect(Session::get($this->getCartSessionKey(), []))
            ->map(fn($data) => $data instanceof SelectedSlot ? $data : SelectedSlot::fromArray($data))
            ->all();
    }

    protected function saveCart(array $cart): void
    {
        $serialized = array_map(
            fn($slot) => $slot instanceof SelectedSlot ? $slot->toArray() : $slot,
            $cart
        );

        Session::put($this->getCartSessionKey(), $serialized);
    }

    protected function getCartSessionKey(): string
    {
        return match (true) {
            filament()->auth()->user() instanceof Customer => 'customer_booking_cart_' . filament()->auth()->id(),
            filament()->auth()->user() instanceof User => 'admin_booking_cart_' . filament()->auth()->id(),
            default => 'guest_booking_cart',
        };
    }

    // ───── Slot and Group Slot Builder ─────

    protected function buildSlotData(string $date, int $courtId, ?string $courtName, int $hour): array
    {
        $start = Carbon::parse("{$date} {$hour}:00");
        $end = $start->copy()->addHour();

        $slot = new SelectedSlot(
            id: uniqid(),
            courtId: $courtId,
            courtName: $courtName ?? "Court #{$courtId}",
            date: $date,
            hour: $hour,
            formattedTime: "{$start->format('H:i')} - {$end->format('H:i')}",
            formattedDate: $start->format('D, j M Y'),
            price: $this->getPricingService()->getPriceForHour($courtId, $date, $start),
        );

        return $slot->toArray();
    }

    protected function groupConsecutiveSlots(Collection $grouped): Collection
    {
        $grouped = $grouped->sortBy('hour')->values();
        $blocks = [];
        $currentBlock = [$grouped->first()];

        for ($i = 1; $i < $grouped->count(); $i++) {
            $prev = $grouped[$i - 1];
            $curr = $grouped[$i];

            if ($curr->hour === $prev->hour + 1) {
                $currentBlock[] = $curr;
            } else {
                $blocks[] = collect($currentBlock);
                $currentBlock = [$curr];
            }
        }

        $blocks[] = collect($currentBlock);

        return collect($blocks)->map(fn($block) => SelectedSlotGroup::fromSelectedSlots($block));
    }


    // ───── Dependencies ─────

    protected function checkSlotConflict(int $courtId, Carbon $start, Carbon $end, array $excludeSlotIds = []): bool
    {
        return $this->getBookingSlotService()->checkSlotConflict($courtId, $start, $end, $excludeSlotIds);
    }

    protected function getBookingSlotService(): BookingSlotService
    {
        return app(BookingSlotService::class);
    }

    protected function getPricingService(): PricingRuleService
    {
        return app(PricingRuleService::class);
    }

    // ───── Event Dispatcher ─────

    protected function fireEvent(string $event): void
    {
        if (method_exists($this, 'dispatch')) {
            $this->dispatch($event);
        }
    }

    // ───── Reschedule Logic ─────

    public function isRescheduled($originalBooking): void
    {
        $originalBookingKey = "{$originalBooking->court_id}_{$originalBooking->date->format('Y-m-d')}_{$originalBooking->starts_at->format('H:i')}_{$originalBooking->ends_at->format('H:i')}";

        $groups = $this->getGroupedSlots();

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        foreach ($groups as $group) {
            $courtId = $group->courtId;
            $courtName = $group->courtName ?? "Court #{$courtId}";
            $date = $group->date;

            $start = Carbon::parse("{$date} {$group->slots->min('hour')}:00");
            $end = Carbon::parse("{$date} {$group->slots->max('hour')}:00")->addHour();

            $slotKey = "{$courtId}_{$date}_{$start->format('H:i')}_{$end->format('H:i')}";

            if ($originalBookingKey === $slotKey) {
                throw ValidationException::withMessages([
                    'cart' => "⚠️ The new booking is the same as the original booking for {$courtName} on {$date} at {$start->format('H:i')} - {$end->format('H:i')}."
                ]);
            }
        }
    }

    public function checkRescheduleConflicts($originalBooking): void
    {
        $this->isRescheduled($originalBooking);
        $this->checkBookingConflicts($originalBooking->slots->pluck('id')->all());
    }
}
