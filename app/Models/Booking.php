<?php

namespace App\Models;

use App\Models\Scopes\HasAttendanceScopes;
use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory, HasStatusScopes, HasAttendanceScopes, HasDateRangeScopes;

    protected $fillable = [
        'uuid',
        'booking_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'court_id',
        'date',
        'starts_at',
        'ends_at',
        'total_price',
        'rescheduled_from_booking_id',
        'status',
        'attendance_status',
        'must_check_in_before',
        'checked_in_at',
        'note',
        'created_by_type',
        'created_by_id',
        'booking_invoice_id',
    ];

    protected $casts = [
        'date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'must_check_in_before' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * The customer who made the booking (nullable).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * The court that is booked.
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * The invoice related to this booking.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(BookingInvoice::class);
    }

    /**
     * All 1-hour booking slots associated with this booking.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    /**
     * The booking that this booking was rescheduled from (if any).
     */
    public function rescheduledFrom(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'rescheduled_from_booking_id');
    }

    /**
     * The booking that this booking was rescheduled to (if any).
     * This is a one-to-one relationship where the current booking is the original.
     */
    public function rescheduledTo(): HasOne
    {
        return $this->hasOne(Booking::class, 'rescheduled_from_booking_id');
    }

    /**
     * The user or customer who created the booking (polymorphic).
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
    }
}
