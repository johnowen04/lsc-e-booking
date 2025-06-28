<?php

namespace App\Livewire\Page;

use App\Models\BookingInvoice;
use Livewire\Component;

class BookingSummaryView extends Component
{
    public $invoice;
    public $isAdmin;

    public function mount(BookingInvoice $invoice, bool $isAdmin = true): void
    {
        $this->invoice = $invoice->load('bookings');
        $this->isAdmin = $isAdmin;
    }
    
    public function render()
    {
        return view('livewire.page.booking-summary-view');
    }
}
