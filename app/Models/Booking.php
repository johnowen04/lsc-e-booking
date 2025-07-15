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
        'booking_invoice_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'court_id',
        'date',
        'starts_at',
        'ends_at',
        'total_price',
        'status',
        'attendance_status',
        'must_check_in_before',
        'note',
        'created_by_type',
        'created_by_id',
        'rescheduled_from_booking_id',
        'reschedule_reason',
        'rescheduled_by_type',
        'rescheduled_by_id',
        'attended_at',
        'cancelled_at',
        'expired_at',
        'rescheduled_at',
    ];

    protected $casts = [
        'uuid' => 'string',
        'date' => 'date',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'must_check_in_before' => 'datetime',
        'attended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'rescheduled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function generateBookingNumber(): string
    {
        if ($this->booking_number) {
            return $this->booking_number;
        }

        $prefix = 'BK-' . $this->date->format('Ymd') . '-';
        $suffix = strtoupper(Str::random(6));
        $this->booking_number = $prefix . $suffix;

        return $this->booking_number;
    }

    public function canAttend(): bool
    {
        return $this->attendance_status === 'no_show' ||
            $this->attendance_status === 'attended' ||
            ($this->attendance_status === 'pending' && !$this->invoice->isPaid());
    }

    public function attendVisible(): bool
    {
        return $this->status === 'confirmed' && $this->attendance_status !== 'attended';
    }

    public function canReschedule(): bool
    {
        return $this->status === 'confirmed' &&
            $this->attendance_status === 'pending' &&
            $this->invoice->isPaid() &&
            $this->rescheduled_from_booking_id === null;
    }

    public function rescheduleVisible(): bool
    {
        return $this->status === 'confirmed' &&
            $this->attendance_status === 'pending' &&
            $this->invoice->isPaid() &&
            $this->rescheduled_from_booking_id === null;
    }

    public function attend(): bool
    {
        if ($this->attendance_status === 'attended') {
            return false;
        }

        if ($this->invoice->status === 'paid') {
            $this->attendance_status = 'attended';
            $this->attended_at = now();
            $this->slots()->each(function ($slot) {
                if ($slot->courtScheduleSlot) {
                    $slot->courtScheduleSlot->update(['status' => 'attended']);
                }
            });
            $this->save();

            return true;
        }

        return false;
    }

    public function slotsGroupedByPricingRule(): array
    {
        return $this->slots()
            ->with('pricingRule')
            ->get()
            ->groupBy(function ($slot) {
                return $slot->pricingRule->name ?? 'Default';
            })
            ->map(function ($group) {
                return [
                    'slots' => $group,
                    'price' => $group->first()->price ?? 0,
                    'total_price' => $group->sum('price'),
                ];
            })
            ->toArray();
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
        return $this->belongsTo(BookingInvoice::class, 'booking_invoice_id');
    }

    /**
     * All 1-hour booking slots associated with this booking.
     */
    public function slots(): HasMany
    {
        return $this->hasMany(BookingSlot::class);
    }

    /**
     * The user or customer who created the booking (polymorphic).
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
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
     * The user who rescheduled the booking (polymorphic).
     * This is used to track who made the rescheduling action.
     */
    public function rescheduledBy(): MorphTo
    {
        return $this->morphTo('rescheduled_by');
    }
}
