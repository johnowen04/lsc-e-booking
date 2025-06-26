<?php

namespace App\Services;

use App\Models\BookingInvoice;
use App\Models\Payment;
use App\Models\Paymentable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function createPayment(
        float $amount,
        Model $invoice,
        ?array $overrides = [],
    ): Payment {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        return DB::transaction(function () use ($amount, $invoice, $overrides) {
            $payment = Payment::create(array_merge([
                'uuid'        => Str::uuid(),
                'amount'      => $amount,
            ], $overrides));

            Paymentable::updateOrCreate([
                'payment_id'       => $payment->id,
                'paymentable_type' => $invoice::class,
                'paymentable_id'   => $invoice->id,
            ]);

            return $payment;
        });
    }

    public function updatePayment(
        string $orderId,
        float $paidAmount,
        string $paymentMethod,
        string $status,
        Model $invoice,
        ?string $referenceCode = null,
        ?string $providerName = null,
        ?string $notes = null,
        ?string $paidAt = null,
        ?string $expiresAt = null,
        ?array $overrides = [],
    ): Payment {
        if ($orderId === '') {
            throw ValidationException::withMessages([
                'order_id' => 'Order ID cannot be empty.',
            ]);
        }

        if ($paidAmount < 0) {
            throw ValidationException::withMessages([
                'paid_amount' => 'Paid amount must be greater than or equals to zero.',
            ]);
        }

        return DB::transaction(function () use (
            $orderId,
            $paidAmount,
            $paymentMethod,
            $status,
            $invoice,
            $referenceCode,
            $providerName,
            $notes,
            $paidAt,
            $expiresAt,
            $overrides,
        ) {
            $payment = Payment::updateOrCreate(
                ['uuid' => $orderId],
                array_merge([
                    'paid_amount'    => $paidAmount,
                    'method'         => $paymentMethod,
                    'status'         => $status,
                    'reference_code' => $referenceCode,
                    'provider_name'  => $providerName,
                    'notes'          => $notes,
                    'paid_at'        => $paidAt,
                    'expires_at'     => $expiresAt,
                ], $overrides)
            );

            Paymentable::updateOrCreate([
                'payment_id'       => $payment->id,
                'paymentable_type' => $invoice::class,
                'paymentable_id'   => $invoice->id,
            ]);

            $this->updateInvoiceStatus($invoice);

            return $payment;
        });
    }

    public function updateInvoiceStatus(Model $invoice): void
    {
        $invoice->updatePaymentStatus();

        if ($invoice instanceof BookingInvoice) {
            $invoice->updateBookings();
        }
    }
}
