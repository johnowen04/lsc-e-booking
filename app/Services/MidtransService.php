<?php

namespace App\Services;

use DateTimeZone;
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

    public function generateSnapUrl(
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

    public function getTransactionStatus(string $orderId): array
    {
        return Transaction::status($orderId);
    }

    public function checkTransactionStatus(string $orderId): string
    {
        $status = $this->getTransactionStatus($orderId);
        return $status['transaction_status'] ?? 'unknown';
    }
}
