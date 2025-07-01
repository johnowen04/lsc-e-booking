<?php

namespace App\Livewire\Page\Booking;

use App\Models\Booking;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Livewire\Component;
use Livewire\WithPagination;

class BookingIndex extends Component
{
    use WithPagination;

    public function render()
    {
        $user = filament()->auth()->user();

        if ($user) {
            $bookings = Booking::with('court')
                ->where('customer_id', $user->id)
                ->latest('starts_at')
                ->paginate(10);
        } else {
            $bookings = new LengthAwarePaginator([], 0, 10, Paginator::resolveCurrentPage());
        }

        return view('livewire.page.booking.booking-index', [
            'bookings' => $bookings,
        ]);
    }
}
