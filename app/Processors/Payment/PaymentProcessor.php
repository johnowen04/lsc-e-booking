<?php

namespace App\Processors\Payment;

use App\DTOs\Payment\CreatePaymentData;
use App\DTOs\Payment\UpdatePaymentData;
use App\Enums\PaymentMethod;
use App\Models\Payment;
use App\Services\MidtransService;
use App\Services\PaymentService;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class PaymentProcessor
{
    public function __construct(
        protected PaymentService $paymentService,
        protected MidtransService $midtransService,
    ) {}

    public function handleCallback(UpdatePaymentData $data): void
    {
        $this->paymentService->updatePayment($data);
    }

    public function handle(CreatePaymentData $data, Closure|string $itemDetailName, string $callbackClass): Payment
    {
        $payment = $this->paymentService->createPayment($data);

        return match ($data->method) {
            PaymentMethod::QRIS->value => $this->handleQris($payment, $data, $callbackClass, $itemDetailName),
            PaymentMethod::CASH->value => $this->handleCash($payment, $data, $callbackClass),
            default => throw ValidationException::withMessages([
                'payment_method' => 'Invalid payment method selected.',
            ]),
        };
    }

    protected function handleQris(Payment $payment, CreatePaymentData $data, string $callbackClass, Closure|string $itemDetailName): Payment
    {
        $this->midtransService->prepareSnapTransaction(
            $data->invoice->resolveModel(),
            $payment,
            $itemDetailName,
            $data->amounts->total === $data->invoice->resolveModel()->getTotalAmount(),
            $callbackClass
        );

        return $payment;
    }

    protected function handleCash(Payment $payment, CreatePaymentData $data, string $callbackClass): Payment
    {
        $updated = new UpdatePaymentData(
            orderId: $payment->uuid,
            amounts: $data->amounts,
            method: PaymentMethod::CASH->value,
            status: 'paid',
            paidAt: now()->toDateTimeString(),
            invoice: $data->invoice,
            createdBy: $data->createdBy
        );

        $payment = $this->paymentService->updatePayment($updated);


        $finishUrl = $callbackClass::getSignedUrl(parameters: [
            'order_id' => $payment->uuid,
            'status_code' => 200,
        ]);

        Cache::put("cash_{$payment->uuid}", $finishUrl, now()->addMinutes(5));

        return $payment;
    }
}
