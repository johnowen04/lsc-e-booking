<?php

namespace App\Filament\Admin\Pages\Report;

use App\Filament\Admin\Widgets\CourtOccupancyTableWidget;
use Filament\Pages\Page;

class CourtOccupancyReport extends Page
{
    protected static string $view = 'filament.admin.pages.report.court-occupancy-report';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Court Occupancy Report';

    protected function getHeaderWidgets(): array
    {
        return [
            // CourtOccupancyStatsWidget::class,
            CourtOccupancyTableWidget::class,
        ];
    }
}
