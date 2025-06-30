<?php

namespace App\Services;

use App\Models\Payment;
use Closure;
use DateTimeZone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$clientKey = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$overrideNotifUrl = route('midtrans.callback');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createTransaction(array $params): string
    {
        return Snap::getSnapUrl($params);
    }

    public function handleNotification(): Notification
    {
        return new Notification();
    }

    public function getTransactionStatus(string $orderId): array
    {
        return Transaction::status($orderId);
    }

    public function checkTransactionStatus(string $orderId): string
    {
        $status = $this->getTransactionStatus($orderId);
        return $status['transaction_status'] ?? 'unknown';
    }

    public function prepareSnapTransaction(
        Model $invoice,
        Payment $payment,
        Closure|string|null $itemDetailName = null,
        bool $isPaidInFull,
        string $callbacFinishUrl,
        string $callbackErrorUrl
    ): void {
        $invoice->load('bookings.court');

        $itemDetails = $invoice->bookings->map(function ($booking) use ($isPaidInFull, $itemDetailName) {
            $price = $isPaidInFull ? $booking->total_price : (int) round($booking->total_price / 2, -2);

            $name = match (true) {
                $itemDetailName instanceof Closure => $itemDetailName($booking),
                is_string($itemDetailName) => $itemDetailName,
                default => "Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
            };

            return [
                'id' => $booking->id,
                'price' => $price,
                'quantity' => 1,
                'name' => $name,
            ];
        })->toArray();

        $expectedTotal = array_sum(array_column($itemDetails, 'price'));

        $snapUrl = $this->generateSnapUrl(
            $payment->uuid,
            $expectedTotal,
            $invoice->customer_name,
            $invoice->customer_phone,
            $itemDetails,
            $callbacFinishUrl,
            $callbackErrorUrl,
        );

        Cache::put("snap_{$payment->uuid}", $snapUrl, now()->addMinutes(5)); //ttl
    }

    protected function generateSnapUrl(
        string $orderId,
        float|int $expectedTotal,
        string $customerName,
        string $customerPhone,
        array $itemDetails,
        ?string $callbackFinishUrl = null,
        ?string $callbackErrorUrl = null,
        ?string $startTime = null,
        string $unit = 'minutes',
        int $duration = 5,
        array $enabledPaymentMethods = ['gopay', 'other_qris'],
    ): string {
        return $this->createTransaction([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $expectedTotal,
            ],
            'customer_details' => [
                'first_name' => $customerName,
                'phone' => $customerPhone,
            ],
            'item_details' => $itemDetails,
            'callbacks' => [
                'finish' => $callbackFinishUrl,
                'error' => $callbackErrorUrl,
            ],
            'expiry' => [
                'start_time' => $startTime ?? now(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i:s O'),
                'unit' => $unit,
                'duration' => $duration, //ttl
            ],
            'enabled_payments' => $enabledPaymentMethods,
        ]);
    }
}
