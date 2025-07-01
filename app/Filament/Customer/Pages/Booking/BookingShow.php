<?php

namespace App\Filament\Customer\Pages\Booking;

use App\Models\Booking;
use Filament\Pages\Page;

class BookingShow extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.customer.pages.booking.booking-show';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Booking Details';

    protected ?string $heading = '';

    public Booking $booking;

    public function mount(): void
    {
        if (request()->get('uuid')) {
            $this->booking = Booking::where('uuid', request()->get('uuid'))->first();
            abort_unless($this->booking->customer_id === filament()->auth()->id(), 403);
            $this->booking = $this->booking->load('court', 'invoice');
        } else {
            redirect(BookingIndex::getUrl());
        }
    }

    public static function canAccess(): bool
    {
        return filament()->auth()->check();
    }
}
