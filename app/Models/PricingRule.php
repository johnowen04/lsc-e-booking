<?php

namespace App\Models;

use App\Models\Scopes\HasDateRangeScopes;
use App\Models\Scopes\HasIsActiveScopes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use HasFactory, HasIsActiveScopes, HasDateRangeScopes;

    protected $fillable = [
        'name',
        'description',
        'court_id',
        'day_of_week',
        'time_start',
        'time_end',
        'start_date',
        'end_date',
        'price_per_hour',
        'type',
        'priority',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'time_start' => 'datetime:H:i:s',
        'time_end' => 'datetime:H:i:s',
        'price_per_hour' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The court this pricing rule applies to (nullable = global).
     */
    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    /**
     * The user (admin or cashier) who created this rule.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
