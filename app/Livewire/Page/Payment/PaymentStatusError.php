<?php

namespace App\Livewire\Page\Payment;

use Livewire\Component;

class PaymentStatusError extends Component
{
    public string $orderId;
    public int $statusCode;

    public function render()
    {
        return view('livewire.page.payment.payment-status-error');
    }
}
