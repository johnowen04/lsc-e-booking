<?php

namespace App\Filament\Admin\Pages\Report;

use App\Filament\Admin\Widgets\CourtIncomeTableWidget;
use Filament\Pages\Page;

class CourtIncomeReport extends Page
{
    protected static string $view = 'filament.admin.pages.report.court-income-report';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Court Income Report';

    protected function getHeaderWidgets(): array
    {
        return [
            // CourtOccupancyStatsWidget::class,
            CourtIncomeTableWidget::class,
        ];
    }
}
