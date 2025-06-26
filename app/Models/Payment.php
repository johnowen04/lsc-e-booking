<?php

namespace App\Models;

use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    use HasFactory, HasStatusScopes, HasDateRangeScopes;

    protected $fillable = [
        'uuid',
        'paid_amount',
        'amount',
        'method',
        'type',
        'status',
        'paid_at',
        'expires_at',
        'reference_code',
        'provider_name',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * The invoices this payment is linked to (morphable: booking, retail, etc).
     */
    public function paymentables(): HasMany
    {
        return $this->hasMany(Paymentable::class);
    }

    /**
     * Summary of invoice
     */
    public function invoice(): ?Model
    {
        return $this->paymentables()->first()?->paymentable;
    }

    /**
     * Polymorphic creator (User or Customer).
     */
    public function createdBy(): MorphTo
    {
        return $this->morphTo('created_by');
    }
}
