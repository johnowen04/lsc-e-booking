<?php

namespace Tests\Support\Traits;

use App\Models\Court;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait BuildsBookingSlots
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildSlots(Court $court, string $date, string $startTime, string $endTime): Collection
    {
        $start = Carbon::parse("{$date} {$startTime}");
        $end = Carbon::parse("{$date} {$endTime}");

        $slots = [];

        while ($start->lt($end)) {
            $next = $start->copy()->addHour();

            $slots[] = [
                'id' => uniqid(),
                'court_id' => $court->id,
                'court_name' => $court->name ?? "Court #{$court->id}",
                'date' => $date,
                'hour' => (int) $start->format('H'),
                'time' => "{$start->format('H:i')} - {$next->format('H:i')}",
                'formatted_date' => $start->format('D, j M Y'),
                'price' => 75000,
            ];

            $start = $next;
        }

        return collect($slots);
    }
}
