<?php

namespace App\Filament\Admin\Pages\Booking;

use App\Models\Booking;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class BookingReschedule extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.booking.booking-reschedule';
    protected static bool $shouldRegisterNavigation = false;

    public ?Booking $originalBooking = null;

    public function getHeading(): string
    {
        return "Reschedule";
    }

    public static function getRoutePath(): string
    {
        return 'bookings/{record}/reschedule';
    }

    public function mount(Booking $record): void
    {
        if (in_array($record->status, ['cancelled', 'expired']) || $record->rescheduled_from_booking_id !== null) {
            abort(403, 'This booking cannot be rescheduled.');
        }
        $this->originalBooking = $record;
    }
}
