<?php

namespace App\Models\Scopes;

trait HasIsActiveScopes
{
    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeIsInactive($query)
    {
        return $query->where('is_active', false);
    }
}
