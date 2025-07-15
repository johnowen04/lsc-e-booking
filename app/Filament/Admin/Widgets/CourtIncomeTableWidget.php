<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Court;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CourtIncomeTableWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    protected static bool $isDiscovered = false;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Court::query()
                    ->select([
                        'courts.id',
                        'courts.name',
                        DB::raw("
                        COALESCE(SUM(
                            CASE
                                WHEN court_schedule_slots.status IN ('attended', 'no_show')
                                THEN court_schedule_slots.price
                                ELSE 0
                            END
                        ), 0) AS total_income
                    "),
                    ])
                    ->leftJoin('court_schedule_slots', 'court_schedule_slots.court_id', '=', 'courts.id')
                    ->groupBy('courts.id', 'courts.name')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Court')
                    ->sortable(),

                TextColumn::make('total_income')
                    ->label('Total Income')
                    ->default(0)
                    ->money('IDR', true)
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date_range')
                    ->form([
                        Grid::make(8)->schema([
                            DatePicker::make('from')
                                ->label('From')
                                ->columnSpan(4)
                                ->default(now()->startOfMonth())
                                ->live(),

                            DatePicker::make('to')
                                ->label('To')
                                ->columnSpan(4)
                                ->default(now())
                                ->live(),
                        ]),
                    ])
                    ->columnSpanFull()
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'] && $data['to'],
                                fn($q) => $q->whereBetween('court_schedule_slots.date', [
                                    \Carbon\Carbon::parse($data['from'])->startOfDay(),
                                    \Carbon\Carbon::parse($data['to'])->endOfDay(),
                                ])
                            )
                            ->when(
                                $data['from'] && !$data['to'],
                                fn($q) => $q->where('court_schedule_slots.date', '>=', \Carbon\Carbon::parse($data['from'])->startOfDay())
                            )
                            ->when(
                                !$data['from'] && $data['to'],
                                fn($q) => $q->where('court_schedule_slots.date', '<=', \Carbon\Carbon::parse($data['to'])->endOfDay())
                            );
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->defaultSort('total_income', 'desc')
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('court-income')->fromTable(),
                ])
            ]);
    }
}
