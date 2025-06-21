<?php

namespace App\Models;

use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BookingInvoice extends Model
{
    use HasFactory, HasStatusScopes, HasDateRangeScopes;

    protected $fillable = [
        'uuid',
        'invoice_number',
        'paid_amount',
        'total_amount',
        'status',
        'issued_at',
        'due_at',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    /**
     * The booking associated with this invoice.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'booking_invoice_id');
    }

    /**
     * Polymorphic creator (User or Customer).
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
    }

    /**
     * Payments related to this invoice (via paymentables).
     */
    public function payments(): MorphMany
    {
        return $this->morphMany(Paymentable::class, 'paymentable');
    }
}
