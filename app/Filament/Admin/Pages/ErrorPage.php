<?php

namespace App\Filament\Admin\Pages;

use App\Models\Payment;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ErrorPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.error-page';

    public ?Model $invoice = null;
    public ?string $orderId = null;

    public function mount(): void
    {
        if (isset(request()['order_id'])) {
            $orderId = request()->query('order_id');
            $this->orderId = $orderId;
            $this->invoice = Payment::where('uuid', $orderId)->first()->invoice();
        }
    }

    public function getHeading(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
