<?php

namespace App\Filament\Admin\Resources\PricingRuleResource\Pages;

use App\Filament\Admin\Resources\PricingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;

class ListPricingRules extends ListRecords
{
    protected static string $resource = PricingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(label: 'New Pricing Rule')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'All' => Tab::make()
                ->query(fn($query) => $query),

            'Regular' => Tab::make()
                ->query(fn($query) => $query->where('type', 'regular')),

            'Peak' => Tab::make()
                ->query(fn($query) => $query->where('type', 'peak')),

            'Promo' => Tab::make()
                ->query(fn($query) => $query->where('type', 'promo')),

            'Custom' => Tab::make()
                ->query(fn($query) => $query->where('type', 'custom')),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Regular';
    }
}
