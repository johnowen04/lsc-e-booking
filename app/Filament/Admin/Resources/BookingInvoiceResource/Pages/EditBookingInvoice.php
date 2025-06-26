<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\Pages;

use App\Filament\Admin\Resources\BookingInvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBookingInvoice extends EditRecord
{
    protected static string $resource = BookingInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
