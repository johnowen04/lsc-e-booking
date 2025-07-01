<?php

namespace App\Filament\Customer\Pages\Booking;

use Filament\Pages\Page;

class BookingIndex extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.customer.pages.booking.booking-index';

    protected static ?string $title = 'My Bookings';

    protected ?string $heading = 'My Bookings';

    protected static ?string $slug = 'my-booking';

    public function mount()
    {
        if (! filament()->auth()->check()) {
            redirect()->to(filament()->getLoginUrl());
        }
    }

    public static function canAccess(): bool
    {
        return filament()->auth()->check();
    }
}
