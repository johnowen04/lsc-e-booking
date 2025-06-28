<?php

namespace App\Livewire\Page\Payment;

use Livewire\Component;

class PaymentStatusView extends Component
{
    public ?string $orderId = null;
    public ?int $statusCode = null;
    public bool $isAdmin = false;

    public function mount(bool $isAdmin): void
    {
        if (!request()->query('order_id')) abort(404);

        $this->orderId = request()->query('order_id');
        $this->statusCode = request()->query('status_code');
        $this->isAdmin = $isAdmin;
    }

    public function render()
    {
        return view('livewire.page.payment.payment-status-view');
    }
}
