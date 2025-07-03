<?php

namespace App\Models\Reports;

use App\Models\Court;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtOccupancySummary extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'integer';

    protected $table = 'court_occupancy_summaries';
    protected $guarded = [];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
