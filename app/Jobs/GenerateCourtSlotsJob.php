<?php

namespace App\Jobs;

use App\Models\Court;
use App\Services\CourtScheduleSlotGeneratorService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateCourtSlotsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Court $court,
        public Carbon $startDate,
        public Carbon $endDate
    ) {}

    public function handle(CourtScheduleSlotGeneratorService $generator): void
    {
        $generator->generateSlotsForCourtAndDateRange($this->court, $this->startDate, $this->endDate);
    }
}
