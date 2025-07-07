<?php

namespace App\DTOs\BookingCart;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class SelectedSlotGroup
{
    public function __construct(
        public string $groupId,
        public int $courtId,
        public string $courtName,
        public string $date,
        public string $formattedDate,
        public string $startsAt,
        public string $endsAt,
        public int $duration,
        public int $price,
        public Collection $slots
    ) {}

    public static function fromSelectedSlots(Collection $slots): self
    {
        $slots = $slots->sortBy('hour')->values();
        $first = $slots->first();
        $last = $slots->last();

        $start = Carbon::parse("{$first->date} {$first->hour}:00");
        $end = Carbon::parse("{$last->date} {$last->hour}:00")->addHour();

        $groupId = md5("{$first->courtId}_{$first->date}_{$first->hour}_{$last->hour}");

        return new self(
            groupId: $groupId,
            courtId: $first->courtId,
            courtName: $first->courtName,
            date: $first->date,
            formattedDate: $first->formattedDate,
            startsAt: $start->format('H:i'),
            endsAt: $end->format('H:i'),
            duration: $slots->count(),
            price: $slots->sum(fn(SelectedSlot $s) => $s->price),
            slots: $slots->values(),
        );
    }

    public function toArray(): array
    {
        return [
            'group_id' => $this->groupId,
            'court_id' => $this->courtId,
            'court_name' => $this->courtName,
            'date' => $this->date,
            'formatted_date' => $this->formattedDate,
            'starts_at' => $this->startsAt,
            'ends_at' => $this->endsAt,
            'duration' => $this->duration,
            'price' => $this->price,
            'slots' => collect($this->slots->map->toArray())->all(),
        ];
    }
}
