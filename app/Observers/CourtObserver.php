<?php

namespace App\Observers;

use App\Models\Court;
use App\Jobs\GenerateCourtSlotsJob;
use Carbon\Carbon;

class CourtObserver
{
    public function created(Court $court): void
    {
        GenerateCourtSlotsJob::dispatch($court, now(), now()->addDays(7));
    }

    public function updated(Court $court): void
    {
        if ($court->isDirty('is_active') && $court->is_active) {
            GenerateCourtSlotsJob::dispatch($court, now(), now()->addDays(7));
        }
    }
}
