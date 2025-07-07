<?php

namespace App\Livewire\Page\Booking;

use App\Traits\InteractsWithBookingCart;
use Filament\Notifications\Notification;
use Livewire\Component;

class BookingCart extends Component
{
    use InteractsWithBookingCart;

    protected $groupedSlots;
    public int $cartTotal = 0;
    public string $checkoutUrl = '';
    public bool $showActions = true;

    protected function getListeners()
    {
        return [
            'slotsAddedToCart' => 'refreshCart',
            'cartCleared' => 'refreshCart',
            'cartItemRemoved' => 'refreshCart',
        ];
    }

    public function getGroupedSlotsProperty(): array
    {
        return $this->groupedSlots->map->toArray();
    }

    public function mount(string $checkoutUrl = 'https://google.com', bool $showActions = true)
    {
        $this->checkoutUrl = $checkoutUrl;
        $this->showActions = $showActions;
        $this->refreshCart();
    }

    public function refreshCart(): void
    {
        $this->groupedSlots = $this->getGroupedSlots();
        $this->cartTotal = $this->calculateCartTotal();
    }

    public function clearCart()
    {
        $this->clearBookingCart();
    }

    public function proceedToCheckout()
    {
        try {
            $this->checkBookingConflicts();
            return redirect($this->checkoutUrl);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Booking Conflict')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }
    }

    public function render()
    {
        return view('livewire.page.booking.booking-cart', [
            'groupedSlots' => $this->groupedSlots,
        ]);
    }
}
