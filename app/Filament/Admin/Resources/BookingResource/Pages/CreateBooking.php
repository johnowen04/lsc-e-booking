<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Pages\BookingSchedule;
use App\Filament\Admin\Resources\BookingInvoiceResource;
use App\Filament\Admin\Resources\BookingResource;
use App\Services\BookingService;
use App\Traits\InteractsWithBookingCart;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateBooking extends CreateRecord
{
    use InteractsWithBookingCart;
    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.admin.resources.booking-resource.pages.create-booking';

    public $groupedSlots;
    public $cartTotal;

    protected BookingService $bookingService;

    public function boot(BookingService $bookingService): void
    {
        $this->bookingService = $bookingService;
    }

    public function mount(): void
    {
        parent::mount();
        $this->fillFromCart();
        $this->cartTotal = $this->calculateCartTotal();
    }

    public function getRedirectUrl(): string
    {
        return BookingInvoiceResource::getUrl('view', [
            'record' => $this->record?->id,
        ]);
    }

    public function getBreadcrumbs(): array
    {
        return [
            BookingResource::getUrl() => 'Booking',
            BookingSchedule::getUrl() => 'Schedule',
            static::getUrl() => 'Create',
        ];
    }

    protected function getListeners(): array
    {
        return [
            'proceedToCheckout' => 'fillFromCart',
            'slotsAddedToCart' => 'fillFromCart',
            'cartItemRemoved' => 'fillFromCart'
        ];
    }

    public function fillFromCart(): void
    {
        $this->groupedSlots = $this->getGroupedSlots();
        $this->cartTotal = $this->calculateCartTotal();
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            $this->checkBookingConflicts();
            $cart = $this->getGroupedSlots();
            $invoice = $this->bookingService->createInvoiceWithBookingsForWalkIn($data, $cart);
            $this->clearBookingCart();
            return $invoice;
        } catch (\Throwable $th) {
            Log::error('Booking creation failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Booking failed')
                ->body('Something went wrong while creating the booking. Please try again or contact admin.')
                ->danger()
                ->persistent()
                ->send();

            throw $th;
        }
    }
}
