<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;

class SuccessPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.success-page';

    public $invoice;
    public $payment;

    public function mount(): void
    {
        $orderId = request()->query('order_id');
        $this->payment = Payment::where('uuid', $orderId)->first();
        $this->invoice = $this->payment->invoice();
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
