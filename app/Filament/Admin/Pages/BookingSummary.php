<?php

namespace App\Filament\Admin\Pages;

use App\Models\BookingInvoice;
use Filament\Pages\Page;

class BookingSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.booking-summary';

    protected static bool $shouldRegisterNavigation = false;

    public $invoice;
    public $payment;

    public function mount(): void
    {
        $invoiceId = request()->query('order_id');

        if ($invoiceId) {
            $this->invoice = BookingInvoice::where('uuid', $invoiceId)->first();
        } else {
            // redirect()->to('/');
        }
    }

    public function getHeading(): string
    {
        return "";
    }
}
