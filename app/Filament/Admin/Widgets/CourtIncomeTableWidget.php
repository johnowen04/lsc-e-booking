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
                    ->selectRaw('courts.id as id, courts.name as court_name, COALESCE(SUM(payments.amount), 0) as total_income')
                    ->leftJoin('bookings', function ($join) {
                        $join->on('bookings.court_id', '=', 'courts.id')
                            ->whereIn('bookings.status', ['confirmed', 'held']);
                    })
                    ->leftJoin('booking_invoices', 'bookings.booking_invoice_id', '=', 'booking_invoices.id')
                    ->leftJoin('paymentables', function ($join) {
                        $join->on('paymentables.paymentable_id', '=', 'booking_invoices.id')
                            ->where('paymentables.paymentable_type', '=', \App\Models\BookingInvoice::class);
                    })
                    ->leftJoin('payments', 'payments.id', '=', 'paymentables.payment_id')
                    ->groupBy('courts.id', 'courts.name')
                    ->orderBy('courts.id')
            )
            ->columns([
                TextColumn::make('court_name')
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
                                fn($q) => $q->whereBetween('payments.created_at', [
                                    \Carbon\Carbon::parse($data['from'])->startOfDay(),
                                    \Carbon\Carbon::parse($data['to'])->endOfDay(),
                                ])
                            )
                            ->when(
                                $data['from'] && !$data['to'],
                                fn($q) => $q->where('payments.created_at', '>=', \Carbon\Carbon::parse($data['from'])->startOfDay())
                            )
                            ->when(
                                !$data['from'] && $data['to'],
                                fn($q) => $q->where('payments.created_at', '<=', \Carbon\Carbon::parse($data['to'])->endOfDay())
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
