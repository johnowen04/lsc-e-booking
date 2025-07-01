<?php

namespace App\Livewire\Page\Payment;

use App\Filament\Admin\Resources\BookingInvoiceResource;
use App\Filament\Customer\Pages\Booking\BookingIndex;
use App\Filament\Customer\Pages\Booking\BookingShow;
use App\Models\BookingInvoice;
use App\Models\Payment;
use Livewire\Component;
use Illuminate\Support\Str;

class PaymentStatusView extends Component
{
    public ?string $orderId = null;
    public ?int $statusCode = null;
    public bool $isAdmin = false;
    public ?string $redirectUrl = null;
    public ?Payment $payment = null;
    public ?BookingInvoice $invoice = null;

    public function mount(bool $isAdmin): void
    {
        if (!request()->query('order_id')) abort(404, 'Payment ID not found');

        $this->orderId = request()->query('order_id');
        if (!Str::isUuid($this->orderId)) {
            abort(404, 'Invalid order ID format');
        }

        $this->payment = Payment::where('uuid', $this->orderId)->first();

        if (!$this->payment) {
            abort(404, 'Payment ID not found');
        }

        $this->statusCode = request()->query('status_code');

        if (in_array($this->statusCode, [200, 201, 202, 203])) {
            $this->invoice = $this->payment->invoice();
            $this->isAdmin = $isAdmin;

            if ($this->isAdmin) {
                $this->redirectUrl = BookingInvoiceResource::getUrl('view', ['record' => $this->invoice->id]);
            } else {
                $this->redirectUrl = filament()->auth()->check() ? BookingIndex::getUrl() : null;
            }
        }
    }

    public function render()
    {
        return view('livewire.page.payment.payment-status-view');
    }
}
