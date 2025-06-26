<?php

namespace App\Filament\Admin\Resources\PricingRuleResource\Pages;

use App\Filament\Admin\Resources\PricingRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePricingRule extends CreateRecord
{
    protected static string $resource = PricingRuleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = filament()->auth()->id();
        return $data;
    }
}
