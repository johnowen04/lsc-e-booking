<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\RelationManagers;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'bookings';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('booking_number')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('booking_number')
            ->columns([
                TextColumn::make('booking_number')
                    ->label('Booking Number')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->booking_number)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('court.name')
                    ->label('Court Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable()
                    ->tooltip(fn($record) => $record->date->format('M d, Y')),
                TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->dateTime('H:i')
                    ->sortable()
                    ->tooltip(fn($record) => $record->starts_at->format('H:i')),
                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime('H:i')
                    ->sortable()
                    ->tooltip(fn($record) => $record->starts_at->format('H:i')),
                TextColumn::make('attendance_status')
                    ->label('Attendance Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'attended' => 'success',
                        'no_show' => 'danger',
                        'cancelled' => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state)),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'held' => 'Held',
                        'confirmed' => 'Confirmed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('confirmed'),
                SelectFilter::make('attendance_status')
                    ->options([
                        'pending' => 'Pending',
                        'attended' => 'Attended',
                        'no_show' => 'No Show',
                        'cancelled' => 'Cancelled',
                    ])
                    ->default('pending')
            ])
            ->headerActions([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn($record) => BookingResource::getUrl('view', [
                        'record' => $record,
                    ])),
                Action::make('attend')
                    ->label('Attend')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->attend();
                        $this->notify('success', 'Booking marked as attended.');
                        return redirect()->to(BookingResource::getUrl('view', ['record' => $record]));
                    })
                    ->disabled(
                        fn($record) => $record->attendance_status === 'attended' ||
                            ($record->attendance_status === 'pending' && !$record->invoice->isPaid()) //ttl (when invoice is paid and status is pending, only enable if now is after the booking time)
                    )
                    ->visible(fn($record) => $record->status === 'confirmed' && $record->attendance_status !== 'attended'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
