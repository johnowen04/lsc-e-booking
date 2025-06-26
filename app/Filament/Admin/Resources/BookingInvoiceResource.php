<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BookingInvoiceResource\Pages;
use App\Filament\Admin\Resources\BookingInvoiceResource\RelationManagers;
use App\Filament\Admin\Resources\BookingInvoiceResource\RelationManagers\BookingsRelationManager;
use App\Filament\Admin\Resources\BookingInvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Models\BookingInvoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BookingInvoiceResource extends Resource
{
    protected static ?string $model = BookingInvoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bookings.booking_number')
                    ->label('Booking Number')
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return $record->bookings->pluck('booking_number')->implode(', ');
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->tooltip(function ($record) {
                        return $record->created_at->format('d M Y H:i:s');
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'gray',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state)),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'gray',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state))
                    ->columnSpanFull(),
                TextEntry::make('invoice_number')
                    ->label('Invoice Number')
                    ->columnSpanFull(),
                TextEntry::make(name: 'customer_name')
                    ->label('Customer Name'),
                TextEntry::make('customer_phone')
                    ->label('Phone Number'),
                TextEntry::make('paid_amount')
                    ->label('Paid Amount')
                    ->money('IDR'),
                TextEntry::make('total_amount')
                    ->label('Total Amount')
                    ->money('IDR'),
                TextEntry::make('createdBy.name')
                    ->label('Created By'),
                TextEntry::make('created_at')
                    ->label('Created At')
                    ->since()
                    ->dateTimeTooltip('d M Y H:i:s'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
            BookingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookingInvoices::route('/'),
            'create' => Pages\CreateBookingInvoice::route('/create'),
            'edit' => Pages\EditBookingInvoice::route('/{record}/edit'),
            'view' => Pages\ViewBookingInvoice::route('/{record}'),
        ];
    }
}
