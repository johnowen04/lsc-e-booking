<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface PayableInterface
{
    public function paymentables(): MorphMany;
    
    public function payments(): HasManyThrough;

    public function getTotalAmount(): float;

    public function updatePaymentStatus(): void;
}
