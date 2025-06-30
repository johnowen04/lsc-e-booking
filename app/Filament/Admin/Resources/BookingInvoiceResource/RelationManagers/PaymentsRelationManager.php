<?php

namespace App\Filament\Admin\Resources\BookingInvoiceResource\RelationManagers;

use App\Enums\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('uuid')
                    ->label('UUID'),
                TextEntry::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y H:i'),
                TextEntry::make('method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn($state) => PaymentMethod::from($state)->label()),
                TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state)),
                TextEntry::make('paid_amount')
                    ->label('Paid Amount')
                    ->money('IDR'),
                TextEntry::make('amount')
                    ->label('Amount')
                    ->money('IDR'),
                TextEntry::make('createdBy.name')
                    ->label('Created By')
                    ->default('System')
                    ->formatStateUsing(fn($state, $record) => $record->createdBy?->name ?? 'System'),
                TextEntry::make('reference_code')
                    ->label('Reference Code')
                    ->default('N/A'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle('Payment')
            ->columns([
                TextColumn::make('paid_at')
                    ->label('Paid At')
                    ->dateTime('d M Y H:i'),
                TextColumn::make('method')
                    ->label('Payment Method')
                    ->formatStateUsing(fn($state) => PaymentMethod::from($state)->label()),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(
                        Sum::make()
                            ->label('Total Amount')
                            ->money('IDR')
                            ->query(fn(QueryBuilder $query) => $query->where('status', 'paid'))
                    ),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'gray',
                        'partially_paid' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => Str::headline($state))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                    ])
                    ->default('paid')
            ])
            ->headerActions([
                //
            ])
            ->actions([
                ViewAction::make()
                    ->label('View')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    //
                ]),
            ]);
    }
}
