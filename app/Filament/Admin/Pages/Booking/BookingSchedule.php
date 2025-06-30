<?php

namespace App\Filament\Admin\Pages\Booking;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Pages\Page;

class BookingSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.booking.booking-schedule';
    protected static bool $shouldRegisterNavigation = false;

    public function getNaviagationLabel(): string
    {
        return "Schedule";
    }

    public function getBreadcrumbs(): array
    {
        return [
            BookingResource::getUrl() => 'Booking',
            static::getUrl() => 'Schedule',
        ];
    }
}
