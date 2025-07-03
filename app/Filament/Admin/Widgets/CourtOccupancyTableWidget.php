<?php

namespace App\Filament\Admin\Widgets;

use App\Models\BookingSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CourtOccupancyTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BookingSlot::query()
                    ->select([
                        'booking_slots.court_id',
                        'courts.name as court_name',
                        DB::raw('COUNT(*) as total_slots'),
                        DB::raw("COUNT(*) FILTER (WHERE booking_slots.status = 'confirmed' OR booking_slots.status = 'held') as booked_slots"),
                        DB::raw("ROUND(
                            COUNT(*) FILTER (WHERE booking_slots.status = 'confirmed' OR booking_slots.status = 'held')::decimal
                            / NULLIF(COUNT(*), 0) * 100, 1
                        ) as booked_rate"),
                        DB::raw("COUNT(*) FILTER (WHERE booking_slots.status = 'attended') as attended_slots"),
                        DB::raw("ROUND(
                            COUNT(*) FILTER (WHERE booking_slots.status = 'attended')::decimal
                            / NULLIF(COUNT(*), 0) * 100, 1
                        ) as occupancy_rate"),
                    ])
                    ->join('courts', 'booking_slots.court_id', '=', 'courts.id')
                    ->groupBy('booking_slots.court_id', 'courts.name')
                    ->orderBy('booking_slots.court_id', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('court_name')
                    ->label('Court Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_slots')
                    ->label('Total Slots')
                    ->numeric(),
                Tables\Columns\TextColumn::make('booked_slots')
                    ->label('Booked Slots')
                    ->numeric(),
                Tables\Columns\TextColumn::make('booked_rate')
                    ->label('Booked Rate (%)')
                    ->numeric(decimalPlaces: 1),
                Tables\Columns\TextColumn::make('attended_slots')
                    ->label('Attended Slots')
                    ->numeric(),
                Tables\Columns\TextColumn::make('occupancy_rate')
                    ->label('Occupancy Rate (%)')
                    ->numeric(decimalPlaces: 1),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(8)->schema([
                            DatePicker::make('from')
                                ->label('From')
                                ->columnSpan(4)
                                ->default(now()), // span 50% of the width
                            DatePicker::make('to')
                                ->label('To')
                                ->columnSpan(4)
                                ->default(now()),
                        ]),
                    ])
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'], fn($q) => $q->where('booking_slots.date', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->where('booking_slots.date', '<=', $data['to']))
                            ->when(
                                $data['from'] && $data['to'],
                                fn($q) => $q->whereBetween('booking_slots.date', [$data['from'], $data['to']])
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('court_id', 'desc')
            ->actions([
                ExportAction::make()
                    ->label('Export')
                    ->exportable(fn($record) => [
                        'Court Name'       => $record->court_name,
                        'Total Slots'      => $record->total_slots,
                        'Booked Slots'     => $record->booked_slots,
                        'Booked Rate (%)'  => $record->booked_rate,
                        'Attended Slots'   => $record->attended_slots,
                        'Occupancy Rate (%)' => $record->occupancy_rate,
                    ])
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->court_id;
    }
}
