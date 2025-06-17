<?php

namespace App\Models;

use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasStatusScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payment extends Model
{
    use HasFactory, HasStatusScopes, HasDateRangeScopes;

    protected $fillable = [
        'amount',
        'method',
        'status',
        'paid_at',
        'reference_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * The invoices this payment is linked to (morphable: booking, retail, etc).
     */
    public function paymentables(): MorphMany
    {
        return $this->morphMany(Paymentable::class, 'payment');
    }
}
