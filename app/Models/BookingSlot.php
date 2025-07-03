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
        'court_schedule_slot_id',
        'date',
        'start_at',
        'end_at',
        'status',
        'price',
        'pricing_rule_id',
        'attended_at',
        'cancelled_at',
        'expired_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'attended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
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

    /**
     * The court schedule slot this booking slot is based on.
     * This can be null if the booking slot is not based on a specific schedule slot.
     */
    public function courtScheduleSlot(): BelongsTo
    {
        return $this->belongsTo(CourtScheduleSlot::class);
    }

    /**
     * The pricing rule applied to this slot.
     */
    public function pricingRule(): BelongsTo
    {
        return $this->belongsTo(PricingRule::class);
    }
}
