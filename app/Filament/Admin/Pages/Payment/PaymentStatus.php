<?php

namespace App\Filament\Admin\Pages\Payment;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class PaymentStatus extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.payment.payment-status';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $orderId = null;
    public ?int $statusCode = null;

    public function mount(): void
    {
        $this->orderId = request()->query('order_id');
        $status = request()->query('status_code');

        if (!$status) {
            $url = Cache::pull("snap_{$this->orderId}") ?? Cache::pull("cash_{$this->orderId}");

            if ($url) redirect()->away($url);
        }

        $this->statusCode = is_numeric($status) ? (int) $status : null;
    }

    public function getHeading(): string
    {
        return "";
    }
}
