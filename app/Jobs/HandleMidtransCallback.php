<?php

namespace App\Jobs;

use App\Models\Payment;
use App\Services\PaymentService;
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
        public Notification $notification
    ) {}

    public function handle(): void
    {
        try {
            $transactionStatus = $this->notification->transaction_status ?? null;
            $transactionId = $this->notification->transaction_id ?? null;
            $statusMessage = $this->notification->status_message ?? null;
            $paymentMethod = $this->notification->payment_type ?? null;
            $orderId = $this->notification->order_id ?? null;
            $grossAmount = $this->notification->gross_amount ?? null;

            $invoice = Payment::where('uuid', $orderId)->first()?->invoice();

            if (!$invoice) {
                Log::warning("No invoice found for Midtrans callback order_id: $orderId");
                return;
            }

            // if ($transactionStatus === 'settlement') {
            if (in_array($transactionStatus, ['settlement', 'capture'])) {
                $settlementTime = $this->notification->settlement_time ?? null;
                $paidAmount = (float) $grossAmount;
                $status = 'paid';
                $paidAt = $settlementTime;
                $expiresAt = null;
            } elseif ($transactionStatus === 'pending') {
                $expiryTime = $this->notification->expiry_time ?? null;
                Log::info("Midtrans transaction pending for order_id: $orderId, expires at: $expiryTime");
                $paidAmount = 0;
                $status = 'pending';
                $paidAt = null;
                $expiresAt = $expiryTime;
            } else {
                $expiryTime = $this->notification->expiry_time ?? null;
                $paidAmount = 0;
                $status = 'failed';
                $paidAt = null;
                $expiresAt = $expiryTime;
            }

            $notes = $statusMessage;

            app(PaymentService::class)->updatePayment(
                $orderId,
                $paidAmount,
                $paymentMethod,
                $status,
                $invoice,
                $transactionId,
                'midtrans',
                $notes,
                $paidAt,
                $expiresAt,
            );
        } catch (\Throwable $th) {
            Log::warning('❌ Error handling Midtrans callback:', [
                'message' => $th->getMessage(),
                'trace' => $th->getTraceAsString(),
            ]);
        }
    }
}
