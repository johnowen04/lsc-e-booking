<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Pages\BookingSchedule;
use App\Filament\Admin\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Booking')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->url(BookingSchedule::getUrl()),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make()
                ->query(fn($query) => $query),

            'Latest' => Tab::make()
                ->query(fn($query) => $query->where('created_at', '>=', now()->subMinutes(15))), //ttl

            'Confirmed' => Tab::make()
                ->query(fn($query) => $query->where('status', 'confirmed')),

            'Cancelled' => Tab::make()
                ->query(fn($query) => $query->where('status', 'cancelled')),

            'Held' => Tab::make()
                ->query(fn($query) => $query->where('status', 'held')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Latest';
    }
}
