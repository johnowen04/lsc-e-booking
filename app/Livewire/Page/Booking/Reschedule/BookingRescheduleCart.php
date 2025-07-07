<?php

namespace App\Livewire\Page\Booking\Reschedule;

use App\Models\Booking;
use App\Traits\InteractsWithBookingCart;
use Filament\Notifications\Notification;
use Livewire\Component;

class BookingRescheduleCart extends Component
{
    use InteractsWithBookingCart;

    protected $groupedSlots;
    public int $cartTotal = 0;
    public string $rescheduleUrl = '';
    public bool $showActions = true;
    public Booking $originalBooking;
    public bool $immutable = false;

    protected function getCartSessionKey(): string
    {
        return 'booking_cart_reschedule';
    }

    protected function getListeners()
    {
        return [
            'slotsAddedToCart' => 'refreshCart',
            'cartCleared' => 'refreshCart',
            'cartItemRemoved' => 'refreshCart',
        ];
    }

    public function mount(string $rescheduleUrl = 'https://google.com', bool $showActions = true, Booking $originalBooking, bool $immutable = false): void
    {
        $this->rescheduleUrl = $rescheduleUrl;
        $this->showActions = $showActions;
        $this->originalBooking = $originalBooking;
        $this->immutable = $immutable;

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

    public function proceedToReschedule()
    {
        try {
            $this->checkRescheduleConflicts($this->originalBooking);
            return redirect($this->rescheduleUrl);
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
        return view('livewire.page.booking.reschedule.booking-reschedule-cart', [
            'groupedSlots' => $this->groupedSlots,
        ]);
    }
}
