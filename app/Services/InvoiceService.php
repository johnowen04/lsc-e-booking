<?php

namespace App\Services;

use App\Models\BookingInvoice;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class InvoiceService
{
    /**
     * Create a BookingInvoice with shared defaults.
     */
    public function createBookingInvoice(array $overrides = []): BookingInvoice
    {
        return BookingInvoice::create(array_merge([
            'uuid' => Str::uuid(),
            'invoice_number' => $this->generateInvoiceNumber('INV'),
            'total_amount' => 0,
            'status' => 'unpaid',
            'issued_at' => now(),
            'due_at' => now()->addHour(), //ttl
        ], $overrides));
    }

    /**
     * Generate a unique invoice number with optional prefix.
     */
    public function generateInvoiceNumber(string $prefix = 'INV'): string
    {
        return $prefix . '-' . now()->format('YmdHis') . '-' . random_int(100, 999);
    }

    /**
     * Get a default due date N hours after now.
     */
    public function calculateDueDate(int $hours = 1): Carbon
    {
        return now()->addHours($hours); //ttl
    }
}
