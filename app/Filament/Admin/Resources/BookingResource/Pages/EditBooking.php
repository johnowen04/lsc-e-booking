<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Actions\Booking\RescheduleBookingFlow;
use App\Filament\Admin\Pages\Booking\BookingReschedule;
use App\Filament\Admin\Resources\BookingResource;
use App\Traits\InteractsWithBookingCart;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class EditBooking extends EditRecord
{
    use InteractsWithBookingCart;

    protected static string $resource = BookingResource::class;

    protected static string $view = 'filament.admin.resources.booking-resource.pages.edit-booking';

    public $originalBooking;
    public $newBooking;

    protected RescheduleBookingFlow $rescheduleBookingFlow;

    public function boot(RescheduleBookingFlow $rescheduleBookingFlow): void
    {
        $this->rescheduleBookingFlow = $rescheduleBookingFlow;
    }

    protected function getCartSessionKey(): string
    {
        return 'booking_cart_reschedule';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getHeading(): string
    {
        return 'Reschedule Booking';
    }

    public function getBreadcrumbs(): array
    {
        return [
            BookingResource::getUrl() => 'Booking',
            BookingReschedule::getUrl(['record' => $this->originalBooking]) => 'Rechedule Table',
            static::getUrl(['record' => $this->originalBooking]) => 'Rechedule',
        ];
    }

    public function getRedirectUrl(): string
    {
        return BookingResource::getUrl('view', ['record' => $this->newBooking]);
    }

    public function mount($record): void
    {
        parent::mount($record);
        $this->originalBooking = $this->record;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            $data['customer_id'] = $this->originalBooking->customer_id;
            $data['customer_name'] = $this->originalBooking->customer_name;
            $data['customer_email'] = $this->originalBooking->customer_email;

            $this->checkRescheduleConflicts($record);

            $this->newBooking = $this->rescheduleBookingFlow->execute(
                $data,
                $record,
                $this->getGroupedSlots()[0],
                [
                    'creator' => filament()->auth()->user(),
                ]
            );

            $this->clearBookingCart();
            return $this->newBooking;
        } catch (\Throwable $th) {
            Log::error('Booking creation failed', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);

            Notification::make()
                ->title('Booking failed')
                ->body($th->getMessage())
                ->danger()
                ->persistent()
                ->send();

            throw $th;
        }
    }
}
