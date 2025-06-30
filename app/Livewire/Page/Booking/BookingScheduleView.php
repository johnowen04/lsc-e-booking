<?php

namespace App\Livewire\Page\Booking;

use App\Traits\InteractsWithBookingCart;
use Closure;
use Livewire\Component;

class BookingScheduleView extends Component
{
    use InteractsWithBookingCart;

    public string $selectedDate;
    public $isAdmin = false;
    public Closure|string $checkoutUrl;
    public bool $hasAnyPricingRule = false;

    public function mount(bool $isAdmin = false, Closure|string $checkoutUrl = 'https://google.com')
    {
        $this->isAdmin = $isAdmin;
        $this->checkoutUrl = $checkoutUrl;
        $this->selectedDate = now()->toDateString();
        $this->hasAnyPricingRule = $this->getPricingService()->hasAnyPricingRule();
    }

    public function render()
    {
        return view('livewire.page.booking.booking-schedule-view');
    }
}
