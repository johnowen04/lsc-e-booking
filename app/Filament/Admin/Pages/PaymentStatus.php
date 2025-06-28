<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class PaymentStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.payment-status';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $orderId = null;
    public ?int $statusCode = null;

    public function mount(): void
    {
        $this->orderId = request()->query('order_id');
        $status = request()->query('status_code');

        $this->statusCode = is_numeric($status) ? (int) $status : null;

        if ($this->orderId === null && $this->statusCode === null) {
            abort(404, 'Order ID not found');
        }

        if ($this->statusCode === null) {
            $url = Cache::pull("snap_{$this->orderId}") ?? Cache::pull("cash_{$this->orderId}");

            if ($url) redirect()->away($url);
        }
    }

    public function getHeading(): string
    {
        return "";
    }
}
