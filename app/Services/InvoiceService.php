<?php

namespace App\Services;

use App\DTOs\BookingInvoice\CreateBookingInvoiceData;
use App\Models\BookingInvoice;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceService
{
    /**
     * Create a BookingInvoice with shared defaults.
     */

    public function createBookingInvoice(CreateBookingInvoiceData $data): BookingInvoice
    {
        return BookingInvoice::create([
            'uuid' => Str::uuid(),
            'invoice_number' => $this->generateInvoiceNumber('INV'),
            'customer_id' => $data->customer->id,
            'customer_name' => $data->customer->name,
            'customer_email' => $data->customer->email,
            'customer_phone' => $data->customer->phone,
            'total_amount' => $data->amount->total,
            'paid_amount' => $data->amount->paid,
            'status' => $data->status,
            'issued_at' => $data->issuedAt,
            'due_at' => $data->dueAt,
            'is_walk_in' => $data->isWalkIn,
            'created_by_type' => $data->createdBy?->type,
            'created_by_id' => $data->createdBy?->id,
        ]);
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
