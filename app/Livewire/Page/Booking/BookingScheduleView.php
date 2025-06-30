<?php

namespace App\Livewire\Page\Booking;

use App\Traits\InteractsWithBookingCart;
use Closure;
use Livewire\Component;

class BookingScheduleView extends Component
{
    use InteractsWithBookingCart;

    public string $selectedDate;
    public $isUser = false;
    public Closure|string $checkoutURL;

    public function mount(bool $isUser = false, Closure|string $checkoutURL = 'https://google.com')
    {
        $this->isUser = $isUser;
        $this->checkoutURL = $checkoutURL;
        $this->selectedDate = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.page.booking.booking-schedule-view');
    }
}
