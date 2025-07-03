<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourtScheduleSlot extends Model
{
    protected $fillable = [
        'court_id',
        'date',
        'start_at',
        'end_at',
        'status',
        'price',
        'pricing_rule_id',
    ];

    protected $casts = [
        'date' => 'date',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function court()
    {
        return $this->belongsTo(Court::class);
    }

    public function bookingSlots()
    {
        return $this->hasMany(BookingSlot::class, 'court_schedule_slot_id');
    }

    public function activeBookingSlot()
    {
        return $this->hasOne(BookingSlot::class, 'court_schedule_slot_id')
            ->whereIn('status', ['held', 'confirmed', 'attended']);
    }
}
