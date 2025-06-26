<?php

namespace App\Traits;

use App\Models\BookingSlot;
use App\Services\PricingRulesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

trait InteractsWithBookingCart
{
    public function isSlotInCart($courtId, $hour)
    {
        $cart = Session::get('booking_cart', []);

        return collect($cart)->contains(function ($slot) use ($courtId, $hour) {
            return $slot['court_id'] === $courtId &&
                $slot['date'] === $this->selectedDate &&
                $slot['hour'] === $hour;
        });
    }

    public function checkBookingConflicts(): void
    {
        $cart = Session::get('booking_cart', []);

        $groupedSlots = collect($cart)->groupBy('group_id');

        if ($groupedSlots->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Cart is empty.',
            ]);
        }

        $groupedSlots->each(function (Collection $group, string $groupId) {
            $first = $group->first();

            if (! $first) {
                throw ValidationException::withMessages([
                    'cart' => "Group [$groupId] is empty.",
                ]);
            }

            $courtId = $first['court_id'];
            $courtName = $first['court_name'] ?? "Court #{$courtId}";
            $date = $first['date'];
            $minHour = $group->min('hour');
            $maxHour = $group->max('hour');

            $slotStart = Carbon::parse("{$date} {$minHour}:00");
            $slotEnd = Carbon::parse("{$date} {$maxHour}:00")->addHour();

            $conflictExists = BookingSlot::query()
                ->where('court_id', $courtId)
                ->whereIn('status', ['confirmed', 'held'])
                ->where(function ($query) use ($slotStart, $slotEnd) {
                    $query->where('slot_start', '<', $slotEnd)
                        ->where('slot_end', '>', $slotStart);
                })
                ->exists();

            if ($conflictExists) {
                throw ValidationException::withMessages([
                    'cart' => "One or more selected slots for {$courtName} on {$date} are already booked.",
                ]);
            }
        });
    }

    public function calculateCartTotal(): int
    {
        $cart = session('booking_cart', []);
        $grouped = collect($cart)->groupBy('group_id');

        return $grouped->sum(function ($group) {
            return $this->calculateGroupTotal($group);
        });
    }

    protected function calculateGroupTotal($group): int
    {
        return collect($group)->sum('price');
    }

    protected function addSlotToCartFlexible(string $date, int $courtId, ?string $courtName, int $startHour, ?int $endHour = null): void
    {
        $cart = session('booking_cart', []);
        $isRange = $endHour !== null && $endHour > $startHour;

        $groupId = $isRange ? uniqid('booking_group_') : null;
        $end = $isRange ? $endHour : $startHour;

        for ($hour = $startHour; $hour <= $end; $hour++) {
            $slotData = $this->buildSlotData($date, $courtId, $courtName, $hour, $groupId);
            $cart[] = $slotData;
        }

        session(['booking_cart' => $cart]);

        $this->dispatch('slotsAddedToCart');
    }

    protected function buildSlotData($date, $courtId, $courtName, $hour, ?string $groupId = null): array
    {
        $slotStart = Carbon::parse("{$date} {$hour}:00");
        $slotEnd = $slotStart->copy()->addHour();

        return [
            'id' => uniqid(),
            'group_id' => $groupId ?? uniqid('booking_group_'),
            'court_id' => $courtId,
            'court_name' => $courtName ?? "Court #{$courtId}",
            'date' => $date,
            'hour' => $hour,
            'time' => "{$slotStart->format('H:i')} - {$slotEnd->format('H:i')}",
            'formatted_date' => Carbon::parse($date)->format('D, j M Y'),
            'price' => app(PricingRulesService::class)->getPriceForHour($courtId, $date, $slotStart),
        ];
    }

    public function getGroupedSlots(): Collection
    {
        $cart = session('booking_cart', []);

        return collect($cart)->groupBy('group_id')->map(function ($group) {
            $firstSlot = $group->first();
            $lastSlot = $group->last();

            $startTime = Carbon::parse("{$firstSlot['date']} {$firstSlot['hour']}:00")->format('H:i');
            $endTime = Carbon::parse("{$lastSlot['date']} {$lastSlot['hour']}:00")->addHour()->format('H:i');

            $totalPrice = $group->sum('price');

            return [
                'group_id' => $firstSlot['group_id'],
                'court_id' => $firstSlot['court_id'],
                'court_name' => $firstSlot['court_name'],
                'date' => $firstSlot['date'],
                'formatted_date' => $firstSlot['formatted_date'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'duration' => count($group),
                'slots' => $group,
                'price' => $totalPrice,
            ];
        })->values();
    }

    public function removeSlot($slotId): void
    {
        $this->removeSlotById($slotId);
    }

    protected function removeSlotById(string $slotId): void
    {
        $cart = Session::get('booking_cart', []);

        $slot = collect($cart)->firstWhere('id', $slotId);

        if (!$slot) return;

        $groupId = $slot['group_id'] ?? null;

        $cart = collect($cart)
            ->reject(fn($item) => $item['group_id'] === $groupId)
            ->values()
            ->toArray();

        Session::put('booking_cart', $cart);

        $this->dispatch('cartItemRemoved');
    }

    public function clearCart(): void
    {
        Session::put('booking_cart', []);
        $this->dispatch('cartCleared');
    }
}
