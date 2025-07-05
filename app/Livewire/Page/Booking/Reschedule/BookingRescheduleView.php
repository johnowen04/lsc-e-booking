<?php

namespace App\Livewire\Page\Booking\Reschedule;

use App\Models\Booking;
use App\Traits\InteractsWithBookingCart;
use Closure;
use Illuminate\Support\Carbon;
use Livewire\Component;

class BookingRescheduleView extends Component
{
    use InteractsWithBookingCart;

    public string $selectedDate;
    public bool $isAdmin = false;
    public Closure|string $rescheduleUrl;
    public bool $hasAnyPricingRule = false;
    public Booking $originalBooking;

    public function mount(
        bool $isAdmin = false,
        Closure|string $rescheduleUrl = 'https://google.com',
        Booking $originalBooking,
    ): void {
        $this->isAdmin = $isAdmin;
        $this->rescheduleUrl = $rescheduleUrl;
        $this->selectedDate = Carbon::now()->toDateString();
        $this->hasAnyPricingRule = $this->getPricingService()->hasAnyPricingRule();
        
        $this->originalBooking = $originalBooking;
    }

    public function render()
    {
        return view('livewire.page.booking.reschedule.booking-reschedule-view');
    }
}
