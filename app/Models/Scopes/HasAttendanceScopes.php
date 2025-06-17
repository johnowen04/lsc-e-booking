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

    public function scopeLate($query)
    {
        return $query->where('attendance_status', 'late');
    }

    public function scopePending($query)
    {
        return $query->where('attendance_status', 'pending');
    }
}
