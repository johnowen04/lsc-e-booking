<?php

namespace App\Livewire;

use App\Filament\Admin\Resources\BookingResource;
use App\Traits\InteractsWithBookingCart;
use Illuminate\Support\Collection;
use Livewire\Component;

class BookingCart extends Component
{
    use InteractsWithBookingCart;

    public $cartTotal = 0;

    protected function getListeners()
    {
        return [
            'slotsAddedToCart' => '$refresh',
            'cartCleared' => '$refresh',
            'cartItemRemoved' => '$refresh'
        ];
    }

    public function proceedToCheckout()
    {
        $this->dispatch('proceedToCheckout');
        return redirect(BookingResource::getUrl('create'));
    }

    public function render()
    {
        $groupedSlots = $this->getGroupedSlots();
        $this->cartTotal = $this->calculateCartTotal();

        return view('livewire.booking-cart', [
            'groupedSlots' => $groupedSlots,
        ]);
    }
}
