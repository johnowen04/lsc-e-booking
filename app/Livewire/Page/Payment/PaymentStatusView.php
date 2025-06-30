<?php

namespace App\Livewire\Page\Payment;

use App\Models\Payment;
use Livewire\Component;
use Illuminate\Support\Str;

class PaymentStatusView extends Component
{
    public ?string $orderId = null;
    public ?int $statusCode = null;
    public bool $isAdmin = false;

    public function mount(bool $isAdmin): void
    {
        if (!request()->query('order_id')) abort(404, 'Order ID not found');

        $this->orderId = request()->query('order_id');
        if (!Str::isUuid($this->orderId)) {
            abort(404, 'Invalid order ID format');
        }

        $payment = Payment::where('uuid', $this->orderId)->first();

        if (!$payment) {
            abort(404, 'Order ID not found');
        }

        $this->statusCode = request()->query('status_code');
        $this->isAdmin = $isAdmin;
    }

    public function render()
    {
        return view('livewire.page.payment.payment-status-view');
    }
}
