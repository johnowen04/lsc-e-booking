<?php

namespace App\Filament\Customer\Pages\Booking;

use Filament\Pages\Page;

class BookingSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.customer.pages.booking.booking-schedule';

    public string $checkoutUrl;

    public function mount(): void
    {
        $this->checkoutUrl = BookingCheckout::getUrl();
    }
}
