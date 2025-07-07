<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CourtScheduleSlot;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CourtOccupancyTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CourtScheduleSlot::query()
                    ->select([
                        'court_id',
                        DB::raw('COUNT(id) as total_slots'),
                        DB::raw("SUM(CASE WHEN status IN ('held', 'confirmed') THEN 1 ELSE 0 END) as booked_slots"),
                        DB::raw("ROUND(1.0 * SUM(CASE WHEN status IN ('held', 'confirmed') THEN 1 ELSE 0 END) / COUNT(id) * 100.0, 1) as booked_rate"),
                        DB::raw("SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) as attended_slots"),
                        DB::raw("ROUND(1.0 * SUM(CASE WHEN status = 'attended' THEN 1 ELSE 0 END) / COUNT(id) * 100.0, 1) as occupancy_rate"),
                    ])
                    ->groupBy('court_id')
                    ->with('court')
                    ->orderBy('court_id', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('court.name')
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
                    ->numeric(decimalPlaces: 1)
                    ->formatStateUsing(fn($state) => "{$state}%"),
                Tables\Columns\TextColumn::make('attended_slots')
                    ->label('Attended Slots')
                    ->numeric(),
                Tables\Columns\TextColumn::make('occupancy_rate')
                    ->label('Occupancy Rate (%)')
                    ->numeric(decimalPlaces: 1)
                    ->formatStateUsing(fn($state) => "{$state}%"),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(8)->schema([
                            DatePicker::make('from')
                                ->label('From')
                                ->columnSpan(4)
                                ->live()
                                ->default(now()), // span 50% of the width
                            DatePicker::make('to')
                                ->label('To')
                                ->columnSpan(4)
                                ->live()
                                ->default(now()),
                        ]),
                    ])
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'] && $data['to'],
                                fn($q) => $q->whereBetween('date', [
                                    \Carbon\Carbon::parse($data['from'])->startOfDay(),
                                    \Carbon\Carbon::parse($data['to'])->endOfDay(),
                                ])
                            )
                            ->when(
                                $data['from'] && !$data['to'],
                                fn($q) => $q->where('date', '>=', \Carbon\Carbon::parse($data['from'])->startOfDay())
                            )
                            ->when(
                                !$data['from'] && $data['to'],
                                fn($q) => $q->where('date', '<=', \Carbon\Carbon::parse($data['to'])->endOfDay())
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('court_id', 'desc')
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable(),
                ])
            ]);
    }

    public function getTableRecordKey($record): string
    {
        return $record->court_id;
    }
}
