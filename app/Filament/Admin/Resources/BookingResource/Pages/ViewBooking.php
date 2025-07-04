<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    public function getHeaderActions(): array
    {
        return [
            Action::make('attend')
                ->label('Attend')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->attend();
                    Notification::make()
                        ->title('Booking Attended')
                        ->body('The booking has been marked as attended.')
                        ->success()
                        ->send();
                    return redirect()->to($this->getResource()::getUrl('view', ['record' => $this->record]));
                })
                ->disabled($this->record->canAttend())
                ->visible($this->record->attendVisible()),
        ];
    }
}
