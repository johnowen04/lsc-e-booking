<?php

namespace App\Filament\Admin\Resources\PricingRuleResource\Pages;

use App\Filament\Admin\Resources\PricingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPricingRule extends EditRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
