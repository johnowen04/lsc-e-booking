<?php

namespace App\Models;

use App\Contracts\PayableInterface;
use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Log;

class BookingInvoice extends Model implements PayableInterface
{
    use HasFactory, HasStatusScopes, HasDateRangeScopes;

    protected $fillable = [
        'uuid',
        'invoice_number',
        'customer_id',
        'customer_name',
        'customer_phone',
        'paid_amount',
        'total_amount',
        'status',
        'issued_at',
        'due_at',
        'created_by_type',
        'created_by_id',
        'is_walk_in',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getTotalAmount(): float
    {
        return (float) $this->total_amount;
    }

    public function getTotalPaidAmount(): float
    {
        return (float) $this->payments()->where('status', 'paid')->sum('paid_amount');
    }

    public function getRemainingAmount(): float
    {
        return (float) ($this->total_amount - $this->getTotalPaidAmount());
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->getTotalPaidAmount();

        $status = match (true) {
            $totalPaid >= $this->total_amount => 'paid',
            $totalPaid > 0 => 'partially_paid',
            default => 'unpaid',
        };

        $this->update(['paid_amount' => $totalPaid, 'status' => $status]);
    }

    public function updateBookings(): void
    {
        $bookings = $this->bookings()->get();
        $newBookingStatus = in_array($this->status, ['paid', 'partially_paid']) ? 'confirmed' : 'held';

        foreach ($bookings as $booking) {
            $booking->update([
                'status' => $newBookingStatus,
                'booking_number' => $booking->generateBookingNumber(),
            ]);

            $booking->slots()->update([
                'status' => $newBookingStatus === 'confirmed' ? 'confirmed' : 'held',
            ]);

            $slotStatus = $newBookingStatus === 'confirmed' ? 'confirmed' : 'held';

            Log::info("✅ Booking ID {$booking->id} updated to status '{$newBookingStatus}'");
            Log::info("✅ BookingSlot(s) for booking ID {$booking->id} updated to '{$slotStatus}'");
        }
    }

    /**
     * The booking associated with this invoice.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'booking_invoice_id');
    }

    /**
     * The customer who made the booking (nullable).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
    public function paymentables(): MorphMany
    {
        return $this->morphMany(Paymentable::class, 'paymentable');
    }

    /**
     * Payments made for this invoice, through the paymentables relationship.
     */
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Paymentable::class,
            'paymentable_id',
            'id',
            'id',
            'payment_id'
        )->where('paymentable_type', BookingInvoice::class);
    }
}
