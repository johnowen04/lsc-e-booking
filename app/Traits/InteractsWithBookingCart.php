<?php

namespace App\Traits;

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
            fn($slot) =>
            $slot['court_id'] === $courtId &&
                $slot['date'] === $date &&
                $slot['hour'] === $hour
        );
    }

    public function checkBookingConflicts(): void
    {
        $groups = $this->getGroupedSlots();

        if ($groups->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Cart is empty.']);
        }

        foreach ($groups as $group) {
            $courtId = $group['court_id'];
            $courtName = $group['court_name'] ?? "Court #{$courtId}";
            $date = $group['date'];

            $start = Carbon::parse("{$date} {$group['slots']->min('hour')}:00");
            $end = Carbon::parse("{$date} {$group['slots']->max('hour')}:00")->addHour();

            if ($this->checkSlotConflict($courtId, $start, $end)) {
                throw ValidationException::withMessages([
                    'cart' => "One or more selected slots for {$courtName} on {$date} are already booked.",
                ]);
            }
        }
    }

    public function calculateCartTotal(): int
    {
        return $this->getGroupedSlots()
            ->sum(fn($group) => $group['price']);
    }

    public function addSlotsToCart(string $date, int $courtId, ?string $courtName, int $startHour, ?int $endHour = null): void
    {
        $cart = $this->getCart();
        $isRange = $endHour !== null && $endHour > $startHour;
        $end = $isRange ? $endHour : $startHour;

        for ($hour = $startHour; $hour <= $end; $hour++) {
            $cart[] = $this->buildSlotData($date, $courtId, $courtName, $hour);
        }

        $this->saveCart($cart);
        $this->fireEvent($this->eventSlotsAdded);
    }

    public function getGroupedSlots(): Collection
    {
        return collect($this->getCart())
            ->sortBy(['court_id', 'date', 'hour'])
            ->groupBy(fn($slot) => "{$slot['court_id']}_{$slot['date']}")
            ->flatMap(function ($grouped) {
                $grouped = $grouped->sortBy('hour')->values();
                $blocks = [];
                $currentBlock = [$grouped->first()];

                for ($i = 1; $i < $grouped->count(); $i++) {
                    $prev = $grouped[$i - 1];
                    $curr = $grouped[$i];

                    if ($curr['hour'] === $prev['hour'] + 1) {
                        $currentBlock[] = $curr;
                    } else {
                        $blocks[] = collect($currentBlock);
                        $currentBlock = [$curr];
                    }
                }

                $blocks[] = collect($currentBlock);

                return collect($blocks)->map(function ($block) {
                    $first = $block->first();
                    $last = $block->last();

                    return [
                        'group_id'        => md5("{$first['court_id']}_{$first['date']}_{$first['hour']}_{$last['hour']}"),
                        'court_id'        => $first['court_id'],
                        'court_name'      => $first['court_name'],
                        'date'            => $first['date'],
                        'formatted_date'  => $first['formatted_date'],
                        'start_time'      => Carbon::parse("{$first['date']} {$first['hour']}:00")->format('H:i'),
                        'end_time'        => Carbon::parse("{$last['date']} {$last['hour']}:00")->addHour()->format('H:i'),
                        'duration'        => $block->count(),
                        'slots'           => $block->values(),
                        'price'           => $block->sum('price'),
                    ];
                });
            })
            ->values();
    }

    public function removeSlot(string $slotId): void
    {
        $cart = collect($this->getCart());
        $updated = $cart->reject(fn($slot) => $slot['id'] === $slotId)->values()->toArray();

        $this->saveCart($updated);
        $this->fireEvent($this->eventItemRemoved);
    }

    public function removeBookingGroup(string $groupId): void
    {
        $slots = $this->getGroupedSlots();
        $group = $slots->firstWhere('group_id', $groupId);

        if (! $group) return;

        $idsToRemove = collect($group['slots'])->pluck('id')->all();

        $cart = collect($this->getCart())
            ->reject(fn($slot) => in_array($slot['id'], $idsToRemove))
            ->values()
            ->toArray();

        $this->saveCart($cart);
        $this->fireEvent($this->eventItemRemoved);
    }

    public function clearBookingCart(): void
    {
        $this->saveCart([]);
        $this->fireEvent($this->eventCartCleared);
    }

    // ───── Protected Methods ─────

    protected function getCartSessionKey(): string
    {
        $key = match (true) {
            filament()->auth()->user() instanceof Customer => 'customer_booking_cart_' . filament()->auth()->id(),
            filament()->auth()->user() instanceof User => 'admin_booking_cart_' . filament()->auth()->id(),
            default => 'guest_booking_cart'
        };

        return $key;
    }

    protected function getCart(): array
    {
        return Session::get($this->getCartSessionKey(), []);
    }

    protected function saveCart(array $cart): void
    {
        Session::put($this->getCartSessionKey(), $cart);
    }

    protected function fireEvent(string $event): void
    {
        $this->dispatch($event);
    }

    protected function buildSlotData(string $date, int $courtId, ?string $courtName, int $hour): array
    {
        $start = Carbon::parse("{$date} {$hour}:00");
        $end = $start->copy()->addHour();

        return [
            'id'              => uniqid(),
            'court_id'        => $courtId,
            'court_name'      => $courtName ?? "Court #{$courtId}",
            'date'            => $date,
            'hour'            => $hour,
            'time'            => "{$start->format('H:i')} - {$end->format('H:i')}",
            'formatted_date'  => Carbon::parse($date)->format('D, j M Y'),
            'price'           => $this->getPricingService()->getPriceForHour($courtId, $date, $start),
        ];
    }

    /**
     * Override this in your component to implement actual logic.
     */
    protected function checkSlotConflict(int $courtId, Carbon $start, Carbon $end): bool
    {
        return $this->getBookingSlotService()->checkSlotConflict($courtId, $start, $end);
    }

    protected function getBookingSlotService(): BookingSlotService
    {
        return app(BookingSlotService::class);
    }

    protected function getPricingService(): PricingRuleService
    {
        return app(PricingRuleService::class);
    }
}
