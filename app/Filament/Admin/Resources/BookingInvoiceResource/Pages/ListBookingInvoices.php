<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\Pages;

use App\Filament\Admin\Resources\BookingInvoiceResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListBookingInvoices extends ListRecords
{
    protected static string $resource = BookingInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make()
                ->query(fn($query) => $query),

            'Latest' => Tab::make()
                ->query(fn($query) => $query->where('created_at', '>=', now()->subMinutes(15))), //ttl

            'Partially Paid' => Tab::make()
                ->query(fn($query) => $query->where('status', 'partially_paid')),

            'Paid' => Tab::make()
                ->query(fn($query) => $query->where('status', 'paid')),

            'Unpaid' => Tab::make()
                ->query(fn($query) => $query->where('status', 'unpaid')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Latest';
    }
}
