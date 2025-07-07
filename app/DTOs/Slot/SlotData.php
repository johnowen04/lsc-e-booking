<?php

namespace App\DTOs\Slot;

use App\DTOs\BookingCart\SelectedSlot;
use App\Models\Court;
use App\Models\CourtScheduleSlot;
use Carbon\Carbon;

readonly class SlotData
{
    public function __construct(
        public int $courtId,
        public string $courtName,
        public string $date,
        public int $hour,
        public string $formattedTime,
        public string $formattedDate,
        public int $price,
        public string $status,
        public bool $isBookable,
        public string $pricingRuleName = '',
    ) {}

    public static function fromScheduleSlot(Court $court, Carbon $slotStart, ?CourtScheduleSlot $slot, Carbon $cutoff): self
    {
        $status = match ($slot?->status) {
            'confirmed' => 'booked',
            'held' => 'held',
            default => 'available',
        };

        return new self(
            courtId: $court->id,
            courtName: $court->name,
            date: $slotStart->toDateString(),
            hour: (int) $slotStart->format('H'),
            formattedTime: self::formatTimeRange($slotStart),
            formattedDate: $slotStart->translatedFormat('D, j M Y'),
            price: $slot?->price ?? 0,
            status: $status,
            isBookable: $status === 'available' && $slotStart->gte($cutoff),
            pricingRuleName: $slot?->pricingRule?->name ?? '',
        );
    }

    public static function fromSelectedSlot(SelectedSlot $selectedSlot): self
    {
        return new self(
            courtId: $selectedSlot->courtId,
            courtName: $selectedSlot->courtName,
            date: $selectedSlot->date,
            hour: $selectedSlot->hour,
            formattedTime: $selectedSlot->formattedTime,
            formattedDate: $selectedSlot->formattedDate,
            price: $selectedSlot->price,
            status: $selectedSlot->status ?? 'available',
            isBookable: $selectedSlot->isBookable ?? true,
            pricingRuleName: $selectedSlot->pricingRuleName ?? '',
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            courtId: $data['court_id'],
            courtName: $data['court_name'],
            date: $data['date'],
            hour: $data['hour'],
            formattedTime: $data['formatted_time'],
            formattedDate: $data['formatted_date'],
            price: $data['price'],
            status: $data['status'],
            isBookable: $data['is_bookable'],
            pricingRuleName: $data['pricing_rule_name'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'court_id' => $this->courtId,
            'court_name' => $this->courtName,
            'date' => $this->date,
            'hour' => $this->hour,
            'formatted_time' => $this->formattedTime,
            'formatted_date' => $this->formattedDate,
            'price' => $this->price,
            'status' => $this->status,
            'is_bookable' => $this->isBookable,
            'pricing_rule_name' => $this->pricingRuleName,
        ];
    }

    protected static function formatTimeRange(Carbon $start): string
    {
        return $start->format('H:i') . ' - ' . $start->copy()->addHour()->format('H:i');
    }
}
