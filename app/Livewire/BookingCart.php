<?php

namespace App\Livewire;

use App\Traits\InteractsWithBookingCart;
use Livewire\Component;

class BookingCart extends Component
{
    use InteractsWithBookingCart;

    public array $groupedSlots = [];
    public int $cartTotal = 0;
    public string $checkoutURL = '';
    public bool $showActions = true;

    protected function getListeners()
    {
        return [
            'slotsAddedToCart' => 'refreshCart',
            'cartCleared' => 'refreshCart',
            'cartItemRemoved' => 'refreshCart',
        ];
    }

    public function mount(string $checkoutURL = 'https://google.com', bool $showActions = true)
    {
        $this->checkoutURL = $checkoutURL;
        $this->showActions = $showActions;
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        $this->groupedSlots = $this->getGroupedSlots()->toArray();
        $this->updateTotal();
    }

    public function updateTotal(): void
    {
        $this->cartTotal = collect($this->getCart())->sum('price');
    }

    public function clearCart()
    {
        $this->clearBookingCart();
        $this->updateTotal();
        $this->dispatch('cartCleared');
    }

    public function proceedToCheckout()
    {
        $this->dispatch('proceedToCheckout');
        return redirect($this->checkoutURL);
    }

    public function render()
    {
        return view('livewire.booking-cart', [
            'groupedSlots' => $this->groupedSlots,
        ]);
    }
}
