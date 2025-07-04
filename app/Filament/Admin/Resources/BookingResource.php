<?php

namespace App\Filament\Admin\Resources;

use App\Enums\PaymentMethod;
use App\Filament\Admin\Resources\BookingResource\Pages;
use App\Filament\Admin\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required(),

                TextInput::make('customer_email')
                    ->label('Customer Email')
                    ->email()
                    ->required()
                    ->dehydrated(),

                Checkbox::make('have_account')
                    ->label('Already have an account?')
                    ->default(false)
                    ->columnSpanFull(),

                // TextInput::make('customer_phone')
                //     ->label('Phone Number')
                //     ->tel()
                //     ->required(),

                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options(PaymentMethod::toArray())
                    ->default('cash')
                    ->required()
                    ->columnSpanFull(),

                Checkbox::make('is_paid_in_full')
                    ->label('Paid in Full')
                    ->default(true)
                    ->live(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_number')
                    ->label('Booking Number')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('court.name')
                    ->label('Court')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Start At')
                    ->dateTime('H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('End At')
                    ->dateTime('H:i')
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label('Customer Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('attendance_status')
                    ->label('Attendance Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'attended' => 'success',
                        'no_show' => 'danger',
                        default => 'info',
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
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::ucfirst($state)),

                TextEntry::make('attendance_status')
                    ->label('Attendance Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'attended' => 'success',
                        'no_show' => 'danger',
                        default => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state)),

                TextEntry::make('booking_number')
                    ->label('Booking Number'),
                TextEntry::make('booking_invoice_id')
                    ->label('Invoice')
                    ->state(fn(Booking $record) => $record->booking_invoice_id ? 'View Invoice' : 'No Invoice')
                    ->icon('heroicon-o-receipt-percent')
                    ->color('primary')
                    ->url(fn(Booking $record) => BookingInvoiceResource::getUrl('view', ['record' => $record->booking_invoice_id]))
                    ->openUrlInNewTab(),

                TextEntry::make(name: 'customer_name')
                    ->label('Customer Name'),

                TextEntry::make('customer_email')
                    ->label('Customer Email'),

                // TextEntry::make('customer_phone')
                //     ->label('Phone Number'),

                TextEntry::make('court.name')
                    ->label('Court'),

                TextEntry::make('date')
                    ->label('Date')
                    ->date('d M Y'),

                TextEntry::make('starts_at')
                    ->label('Start At')
                    ->dateTime('H:i'),

                TextEntry::make('ends_at')
                    ->label('End At')
                    ->dateTime('H:i'),

                TextEntry::make('total_price')
                    ->label('Total Price')
                    ->money('IDR')
                    ->columnSpanFull(),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'view' => Pages\ViewBooking::route('/{record}'),
        ];
    }
}
