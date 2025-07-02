<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Customer extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $table = 'customers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'phone_verification_code',
        'password',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'phone_verification_code',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    /**
     * Automatically hash password when set.
     */
    public function setPasswordAttribute($value)
    {
        if (filled($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Relationship: bookings made by this customer.
     */
    public function bookings()
    {
        return $this->morphMany(Booking::class, 'created_by');
    }

    /**
     * Relationship: booking invoices made by this customer.
     */
    public function bookingInvoices()
    {
        return $this->morphMany(BookingInvoice::class, 'created_by');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return str_ends_with($this->email, '@company.com');
    }
}
