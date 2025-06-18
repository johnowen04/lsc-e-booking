<?php

namespace App\Models;

use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingSlot extends Model
{
    use HasFactory, HasStatusScopes, HasDateRangeScopes;

    protected $fillable = [
        'booking_id',
        'court_id',
        'slot_start',
        'slot_end',
        'status',
    ];

    protected $casts = [
        'slot_start' => 'datetime',
        'slot_end' => 'datetime',
    ];

    /**
     * The booking this slot belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * The court this slot is assigned to.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
