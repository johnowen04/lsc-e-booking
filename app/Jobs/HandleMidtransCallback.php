<?php

namespace App\Jobs;

use App\DTOs\Payment\UpdatePaymentData;
use App\DTOs\Shared\CreatedByData;
use App\DTOs\Shared\InvoiceReference;
use App\DTOs\Shared\MoneyData;
use App\Models\Payment;
use App\Processors\Payment\PaymentProcessor;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;

class HandleMidtransCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Notification $notification,
    ) {}

    public function handle(): void
    {
        $paymentProcessor = app(PaymentProcessor::class);

        try {
            $notification = $this->notification;
            $transactionStatus = $notification->transaction_status ?? null;
            $transactionId = $notification->transaction_id ?? null;
            $statusMessage = $notification->status_message ?? null;
            $paymentMethod = $notification->payment_type ?? 'qris';
            $orderId = $notification->order_id ?? null;
            $grossAmount = (float) ($notification->gross_amount ?? 0);

            $invoice = Payment::where('uuid', $orderId)->first()?->invoice();

            if (!$invoice) {
                Log::warning("No invoice found for Midtrans callback order_id: {$orderId}");
                return;
            }

            $status = match ($transactionStatus) {
                'settlement', 'capture' => 'paid',
                'pending' => 'pending',
                default => 'failed',
            };

            $paidAmount = in_array($transactionStatus, ['settlement', 'capture']) ? $grossAmount : 0;

            $paidAt = $status === 'paid' && $notification->settlement_time
                ? Carbon::parse($notification->settlement_time)
                : null;

            $expiresAt = $status !== 'paid' && $notification->expiry_time
                ? Carbon::parse($notification->expiry_time)
                : null;

            $updateData = new UpdatePaymentData(
                orderId: $orderId,
                amounts: new MoneyData(
                    total: (float) $invoice->total_amount,
                    paid: $paidAmount,
                ),
                method: $paymentMethod,
                status: $status,
                referenceCode: $transactionId,
                providerName: 'midtrans',
                notes: $statusMessage,
                paidAt: $paidAt,
                expiresAt: $expiresAt,
                invoice: new InvoiceReference(
                    type: get_class($invoice),
                    id: $invoice->id,
                ),
                createdBy: new CreatedByData(),
            );

            $paymentProcessor->handleCallback($updateData);
        } catch (\Throwable $th) {
            Log::warning('❌ Error handling Midtrans callback', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
        }
    }
}
