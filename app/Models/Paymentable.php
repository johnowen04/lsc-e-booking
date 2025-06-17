<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Paymentable extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'payment_id',
        'paymentable_type',
        'paymentable_id',
    ];

    /**
     * The invoice or document this payment is applied to.
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The payment record associated with this pivot.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
