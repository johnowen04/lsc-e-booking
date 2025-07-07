<?php

namespace App\DTOs\BookingCart;

use App\DTOs\Slot\SlotData;
use Carbon\Carbon;

class SelectedSlot
{
    public function __construct(
        public string $id,
        public int $courtId,
        public string $courtName,
        public string $date,
        public int $hour,
        public string $formattedTime,
        public string $formattedDate,
        public int $price,
        public ?string $status = null,
        public ?bool $isBookable = null,
        public array $meta = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? uniqid(),
            courtId: $data['court_id'],
            courtName: $data['court_name'] ?? "Court #{$data['court_id']}",
            date: $data['date'],
            hour: $data['hour'],
            formattedTime: $data['formatted_time'] ?? self::formatTimeRange($data['date'], $data['hour']),
            formattedDate: $data['formatted_date'] ?? Carbon::parse($data['date'])->format('D, j M Y'),
            price: $data['price'] ?? 0,
            status: $data['status'] ?? null,
            isBookable: $data['is_bookable'] ?? null,
            meta: $data['meta'] ?? [],
        );
    }

    public static function fromSlotData(SlotData $data): self
    {
        return new self(
            id: uniqid(),
            courtId: $data->courtId,
            courtName: $data->courtName,
            date: $data->date,
            hour: $data->hour,
            formattedTime: $data->formattedTime,
            formattedDate: $data->formattedDate,
            price: $data->price,
            status: $data->status,
            isBookable: $data->isBookable,
            meta: [],
        );
    }

    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'court_id' => $this->courtId,
            'court_name' => $this->courtName,
            'date' => $this->date,
            'hour' => $this->hour,
            'formatted_time' => $this->formattedTime,
            'formatted_date' => $this->formattedDate,
            'price' => $this->price,
            'status' => $this->status,
            'is_bookable' => $this->isBookable,
        ], $this->meta);
    }

    public static function formatTimeRange(string $date, int $hour): string
    {
        $start = Carbon::parse("{$date} {$hour}:00");
        $end = $start->copy()->addHour();

        return "{$start->format('H:i')} - {$end->format('H:i')}";
    }
}
