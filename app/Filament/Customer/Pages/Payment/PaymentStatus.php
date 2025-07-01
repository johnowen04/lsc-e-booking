<?php

namespace App\Filament\Customer\Pages\Payment;

use App\Traits\HasSignedUrl;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class PaymentStatus extends Page
{
    use HasSignedUrl;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.customer.pages.payment.payment-status';

    protected static bool $shouldRegisterNavigation = false;

    public ?string $orderId = null;
    public ?int $statusCode = null;

    public function mount(): void
    {
        $this->orderId = request()->query('order_id');
        $status = request()->query('status_code');

        if (!$status) {
            $url = Cache::pull("snap_{$this->orderId}");

            if ($url) redirect()->away($url);
        }

        $this->statusCode = is_numeric($status) ? (int) $status : null;

        if (request()->query('order_id') && request()->query('status_code')) {
            if (! request()->hasValidSignature()) {
                abort(403);
            }
        }
    }

    public function getHeading(): string
    {
        return "";
    }
}
