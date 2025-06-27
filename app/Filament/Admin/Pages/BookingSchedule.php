<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\BookingResource;
use App\Traits\InteractsWithBookingCart;
use Filament\Pages\Page;

class BookingSchedule extends Page
{
    use InteractsWithBookingCart;

    public string $selectedDate;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.admin.pages.booking-schedule';
    protected static bool $shouldRegisterNavigation = false;

    public function mount(): void
    {
        $this->selectedDate = now()->toDateString();
    }

    protected function getListeners(): array
    {
        return [
            'addBookingToCart' => 'addBookingToCart',
            'clearCart' => 'clearCart',
            'proceedToCheckout' => 'proceedToCheckout',
        ];
    }

    public function clearCart(): void
    {
        $this->clearBookingCart();
        $this->dispatch('cartCleared');
    }

    public function proceedToCheckout(): void
    {
        $this->dispatch('proceedToCheckout');
        redirect(BookingResource::getUrl('create'));
    }

    public function addBookingToCart(array $slotData): void
    {
        $this->addSlotsToCart(
            $slotData['date'],
            $slotData['court_id'],
            $slotData['court_name'],
            $slotData['start_hour'],
            $slotData['end_hour'] ?? null
        );

        $this->dispatch('slotsAddedToCart');
    }

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

    protected function getViewData(): array
    {
        return [
            'selectedDate' => $this->selectedDate,
        ];
    }
}
