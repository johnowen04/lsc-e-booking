<?php

namespace App\Services;

use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Payment\UpdatePaymentData;
use App\Models\BookingInvoice;
use App\Models\Payment;
use App\Models\Paymentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function createPayment(CreatePaymentData $data): Payment
    {
        if ($data->amounts->total <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $payment = Payment::create([
                'uuid' => Str::uuid(),
                'amount' => $data->amounts->total,
            ]);

            Paymentable::updateOrCreate([
                'payment_id' => $payment->id,
                'paymentable_type' => $data->invoice->type,
                'paymentable_id' => $data->invoice->id,
            ]);

            return $payment;
        });
    }

    public function updatePayment(UpdatePaymentData $data): Payment
    {
        if ($data->orderId === '') {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID cannot be empty.',
            ]);
        }

        if ($data->amounts->paid < 0) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount must be greater than or equals to zero.',
            ]);
        }

        return DB::transaction(function () use ($data) {
            $payment = Payment::updateOrCreate(
                ['uuid' => $data->orderId],
                [
                    'paid_amount' => $data->amounts->paid,
                    'method' => $data->method,
                    'status' => $data->status,
                    'reference_code' => $data->referenceCode,
                    'provider_name' => $data->providerName,
                    'notes' => $data->notes,
                    'paid_at' => $data->paidAt,
                    'expires_at' => $data->expiresAt,
                    'created_by_type' => $data->createdBy?->type,
                    'created_by_id' => $data->createdBy?->id,
                ]
            );

            Paymentable::updateOrCreate([
                'payment_id' => $payment->id,
                'paymentable_type' => $data->invoice->type,
                'paymentable_id' => $data->invoice->id,
            ]);

            $this->updateInvoiceStatus($data->invoice->resolveModel());

            return $payment;
        });
    }

    public function updateInvoiceStatus(Model $invoice): void
    {
        Log::info("➡️ Starting updateInvoiceStatus() for Invoice #{$invoice->id}");

        DB::transaction(function () use ($invoice) {
            $invoice->updatePaymentStatus();

            if ($invoice instanceof BookingInvoice) {
                $invoice->updateBookings();
            }
        });

        Log::info("✅ Finished updateInvoiceStatus() for Invoice #{$invoice->id}");
    }
}
