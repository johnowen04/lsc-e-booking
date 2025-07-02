<?php

namespace App\Models;

use App\Models\Scopes\HasIsActiveScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory, HasIsActiveScopes;
    
    protected $fillable = [
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the bookings associated with this court.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the pricing rules for this court.
     */
    public function pricingRules(): HasMany
    {
        return $this->hasMany(PricingRule::class);
    }
}
