<?php

namespace App\Models\Scopes;

trait HasAttendanceScopes
{
    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'attended');
    }

    public function scopeNoShow($query)
    {
        return $query->where('attendance_status', 'no_show');
    }

    public function scopePending($query)
    {
        return $query->where('attendance_status', 'pending');
    }

    public function scopeNotAttended($query)
    {
        return $query->where('attendance_status', '!=', 'attended');
    }

    public function scopeUnattended($query)
    {
        return $query->whereIn('attendance_status', ['pending', 'no_show']);
    }
}
