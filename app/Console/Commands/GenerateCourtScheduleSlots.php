<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CourtScheduleSlotGeneratorService;
use Carbon\Carbon;

class GenerateCourtScheduleSlots extends Command
{
    protected $signature = 'schedule:slots
        {--days=7 : Number of future days to generate}
        {--refresh-pricing : Refresh pricing for future slots too}';

    protected $description = 'Generate court schedule slots and optionally refresh prices.';

    public function handle()
    {
        $days = (int) $this->option('days');
        $from = now()->startOfDay();
        $to = now()->addDays($days)->endOfDay();

        $this->info("⏳ Generating court schedule slots from {$from->toDateString()} to {$to->toDateString()}...");

        app(CourtScheduleSlotGeneratorService::class)
            ->generateSlotsForDateRange($from, $to);

        $this->info("✅ Done generating schedule slots for {$days} days.");

        if ($this->option('refresh-pricing')) {
            $this->info("🔄 Refreshing prices...");
            app(CourtScheduleSlotGeneratorService::class)
                ->refreshSlotPrices($from, $to);
            $this->info("✅ Prices refreshed.");
        }
    }
}
