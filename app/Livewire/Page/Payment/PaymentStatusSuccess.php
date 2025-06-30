<?php

namespace App\Livewire\Page\Payment;

use App\Filament\Admin\Pages\Booking\BookingSummary as AdminBookingSummary;
use App\Filament\Customer\Pages\Booking\BookingSummary as CustomerBookingSummary;
use App\Models\Payment;
use Livewire\Component;

class PaymentStatusSuccess extends Component
{
    public string $invoiceId;
    public string $orderId;
    public int $statusCode;
    public string $redirectUrl;

    public function mount(string $orderId, string $statusCode, bool $isAdmin = true)
    {
        $payment = Payment::where('uuid', $orderId)->first();
        $this->invoiceId = $payment->invoice()->uuid;

        if (!$this->invoiceId) abort(404, 'Order ID not found');

        $this->statusCode = (int) $statusCode;
        $this->redirectUrl = $isAdmin ?
            AdminBookingSummary::getUrl(['order_id' => $this->invoiceId]) :
            CustomerBookingSummary::getUrl(['order_id' => $this->invoiceId]);
    }

    public function render()
    {
        return view('livewire.page.payment.payment-status-success');
    }
}
