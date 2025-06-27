<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;

class BookingSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.customer.pages.booking-schedule';

    public static function canAccess(): bool
    {
        return true;
    }
}
