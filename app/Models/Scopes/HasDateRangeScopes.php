<?php

namespace App\Models\Scopes;

trait HasDateRangeScopes
{
    public function scopeUpcoming($query, $column = 'starts_at')
    {
        return $query->where($column, '>', now());
    }

    public function scopeToday($query, $column = 'starts_at')
    {
        return $query->whereDate($column, now()->toDateString());
    }

    public function scopeBetween($query, $column, $start, $end)
    {
        return $query->whereBetween($column, [$start, $end]);
    }
}
