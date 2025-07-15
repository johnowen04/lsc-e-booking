<?php

namespace App\Jobs;

use App\Services\CourtScheduleSlotGeneratorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshCourtSlotPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $courtId,
        public ?int $dayOfWeek,  // nullable
        public string $timeStart, // 'HH:MM:SS'
        public string $timeEnd    // 'HH:MM:SS'
    ) {}

    public function handle(CourtScheduleSlotGeneratorService $generator): void
    {
        $start = now()->startOfDay();
        $end = $start->copy()->addDays(7)->endOfDay();

        // Refresh all slots in range using service method
        $slots = $generator->getSlotsForCourtDateRange($this->courtId, $start, $end)
            ->filter(function ($slot) {
                $slotTime = Carbon::parse($slot->start_at);
                $slotHour = $slotTime->format('H:i:s');

                $dayMatch = is_null($this->dayOfWeek) || $slotTime->dayOfWeek === $this->dayOfWeek;

                // Handle full-day pricing case (00:00 to 00:00)
                if ($this->timeStart === '00:00:00' && $this->timeEnd === '00:00:00') {
                    return $dayMatch;
                }

                // Handle partial range and overnight ranges
                $hourMatch = $this->timeStart < $this->timeEnd
                    ? ($slotHour >= $this->timeStart && $slotHour < $this->timeEnd)
                    : ($slotHour >= $this->timeStart || $slotHour < $this->timeEnd);

                return $dayMatch && $hourMatch;
            });

        foreach ($slots as $slot) {
            $generator->updateSlotPricing($slot);
        }
    }
}
