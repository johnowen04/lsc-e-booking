<?php

namespace App\Traits;

use App\Services\PricingRulesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

trait InteractsWithBookingCart
{
    protected string $cartSessionKey = 'booking_cart';

    protected string $eventCartCleared = 'cartCleared';
    protected string $eventItemRemoved = 'cartItemRemoved';
    protected string $eventSlotsAdded = 'slotsAddedToCart';

    // ───── Public API ─────

    public function isSlotInCart(string $date, int $courtId, int $hour): bool
    {
        return collect($this->getCart())->contains(
            fn($slot) =>
            $slot['court_id'] === $courtId &&
                $slot['date'] === $date &&
                $slot['hour'] === $hour
        );
    }

    public function checkBookingConflicts(): void
    {
        $cart = $this->getCart();
        $grouped = collect($cart)->groupBy('group_id');

        if ($grouped->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        $grouped->each(function (Collection $group, string $groupId) {
            $first = $group->first();

            if (! $first) {
                throw ValidationException::withMessages([
                    'cart' => "Group [$groupId] is empty.",
                ]);
            }

            $courtId = $first['court_id'];
            $courtName = $first['court_name'] ?? "Court #{$courtId}";
            $date = $first['date'];

            $start = Carbon::parse("{$date} {$group->min('hour')}:00");
            $end = Carbon::parse("{$date} {$group->max('hour')}:00")->addHour();

            $conflict = $this->checkSlotConflict($courtId, $start, $end);

            if ($conflict) {
                throw ValidationException::withMessages([
                    'cart' => "One or more selected slots for {$courtName} on {$date} are already booked.",
                ]);
            }
        });
    }

    public function calculateCartTotal(): int
    {
        return collect($this->getCart())
            ->groupBy('group_id')
            ->sum(fn($group) => $this->calculateGroupTotal($group));
    }

    public function addSlotsToCart(string $date, int $courtId, ?string $courtName, int $startHour, ?int $endHour = null): void
    {
        $cart = $this->getCart();
        $isRange = $endHour !== null && $endHour > $startHour;
        $groupId = uniqid('booking_group_');
        $end = $isRange ? $endHour : $startHour;

        for ($hour = $startHour; $hour <= $end; $hour++) {
            $slot = $this->buildSlotData($date, $courtId, $courtName, $hour, $groupId);
            $cart[] = $slot;
        }

        $this->saveCart($cart);
        $this->fireEvent($this->eventSlotsAdded);
    }

    public function getGroupedSlots(): Collection
    {
        return collect($this->getCart())
            ->groupBy('group_id')
            ->map(function ($group) {
                $first = $group->first();
                $last = $group->last();

                return [
                    'group_id'        => $first['group_id'],
                    'court_id'        => $first['court_id'],
                    'court_name'      => $first['court_name'],
                    'date'            => $first['date'],
                    'formatted_date'  => $first['formatted_date'],
                    'start_time'      => Carbon::parse("{$first['date']} {$first['hour']}:00")->format('H:i'),
                    'end_time'        => Carbon::parse("{$last['date']} {$last['hour']}:00")->addHour()->format('H:i'),
                    'duration'        => count($group),
                    'slots'           => $group,
                    'price'           => $group->sum('price'),
                ];
            })
            ->values();
    }

    public function removeSlot(string $slotId): void
    {
        $cart = collect($this->getCart());
        $slot = $cart->firstWhere('id', $slotId);

        if (! $slot) return;

        $groupId = $slot['group_id'];
        $updated = $cart->reject(fn($item) => $item['group_id'] === $groupId)->values()->toArray();

        $this->saveCart($updated);
        $this->fireEvent($this->eventItemRemoved);
    }

    public function clearBookingCart(): void
    {
        $this->saveCart([]);
        $this->fireEvent($this->eventCartCleared);
    }

    // ───── Protected Methods ─────

    protected function getCart(): array
    {
        return Session::get($this->cartSessionKey, []);
    }

    protected function saveCart(array $cart): void
    {
        Session::put($this->cartSessionKey, $cart);
    }

    protected function fireEvent(string $event): void
    {
        $this->dispatch($event);
    }

    protected function buildSlotData(string $date, int $courtId, ?string $courtName, int $hour, string $groupId): array
    {
        $start = Carbon::parse("{$date} {$hour}:00");
        $end = $start->copy()->addHour();

        return [
            'id'              => uniqid(),
            'group_id'        => $groupId,
            'court_id'        => $courtId,
            'court_name'      => $courtName ?? "Court #{$courtId}",
            'date'            => $date,
            'hour'            => $hour,
            'time'            => "{$start->format('H:i')} - {$end->format('H:i')}",
            'formatted_date'  => Carbon::parse($date)->format('D, j M Y'),
            'price'           => $this->getPricingService()->getPriceForHour($courtId, $date, $start),
        ];
    }

    protected function calculateGroupTotal(Collection $group): int
    {
        return $group->sum('price');
    }

    /**
     * Override this in your component to implement actual logic.
     */
    protected function checkSlotConflict(int $courtId, Carbon $start, Carbon $end): bool
    {
        return \App\Models\BookingSlot::query()
            ->where('court_id', $courtId)
            ->whereIn('status', ['confirmed', 'held'])
            ->where(
                fn($query) =>
                $query->where('slot_start', '<', $end)
                    ->where('slot_end', '>', $start)
            )
            ->exists();
    }

    /**
     * Override this to inject a mock or custom pricing rule logic.
     */
    protected function getPricingService(): PricingRulesService
    {
        return app(PricingRulesService::class);
    }
}
