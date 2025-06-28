<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Filament\Admin\Pages\PaymentStatus as AdminPaymentStatus;
use App\Filament\Customer\Pages\PaymentStatus as CustomerPaymentStatus;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Customer;
use App\Models\BookingInvoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        protected MidtransService $midtransService,
        protected InvoiceService $invoiceService,
        protected PricingRulesService $pricingRulesService,
        protected PaymentService $paymentService,
    ) {}

    public function createInvoiceWithBookingsForOnline(array $data, $cart): Payment
    {
        return DB::transaction(function () use ($data, $cart) {
            $invoice = $this->createInvoiceFromCart($data, $cart, false);
            $amount = $this->calculatePaymentAmount((float) $invoice->total_amount, $data['is_paid_in_full']);

            $payment = $this->paymentService->createPayment(
                $amount,
                $invoice,
                overrides: [
                    'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
                    'created_by_id' => filament()->auth()->id(),
                ]
            )->fresh();

            $this->generateSnapUrlForInvoice(
                $invoice,
                $payment,
                $data['is_paid_in_full'],
                callbackFinishUrl: CustomerPaymentStatus::getUrl(['order_id' => $payment->uuid]),
                callbackErrorUrl: CustomerPaymentStatus::getUrl(['order_id' => $payment->uuid]),
            );

            return $payment;
        });
    }

    public function createInvoiceWithBookingsForWalkIn(array $data, $cart): Payment
    {
        return DB::transaction(function () use ($data, $cart) {
            $invoice = $this->createInvoiceFromCart($data, $cart, true);
            $amount = $this->calculatePaymentAmount((float) $invoice->total_amount, $data['is_paid_in_full']);

            $payment = $this->paymentService->createPayment(
                $amount,
                $invoice,
                overrides: [
                    'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
                    'created_by_id' => filament()->auth()->id(),
                ],
            );

            if (PaymentMethod::from($data['payment_method']) === PaymentMethod::QRIS) {
                $this->generateSnapUrlForInvoice(
                    $invoice,
                    $payment,
                    $data['is_paid_in_full'],
                    callbackFinishUrl: AdminPaymentStatus::getUrl(['order_id' => $payment->uuid]),
                    callbackErrorUrl: AdminPaymentStatus::getUrl(['order_id' => $payment->uuid]),
                );
            } else if (PaymentMethod::from($data["payment_method"]) === PaymentMethod::CASH) {
                $payment = app(PaymentService::class)->updatePayment(
                    $payment->uuid,
                    (float) $payment->amount,
                    PaymentMethod::CASH->value,
                    'paid',
                    $invoice,
                    paidAt: now()->toDateTimeString(),
                    overrides: [
                        'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
                        'created_by_id' => filament()->auth()->id(),
                    ],
                );

                $redirectUrl = AdminPaymentStatus::getUrl(['order_id' => $payment->uuid]);
                Cache::put("cash_{$payment->uuid}", $redirectUrl, now()->addMinutes(5)); //ttl
            } else {
                throw ValidationException::withMessages(['payment_method' => 'Invalid payment method selected.']);
            }

            return $payment;
        });
    }

    protected function generateSnapUrlForInvoice(
        BookingInvoice $invoice,
        Payment $payment,
        bool $isPaidInFull,
        string $callbackFinishUrl,
        string $callbackErrorUrl
    ): string {
        $invoice->load('bookings.court');

        $itemDetails = $invoice->bookings->map(function ($booking) use ($isPaidInFull) {
            $price = $isPaidInFull ? $booking->total_price : (int) round($booking->total_price / 2, -2);
            return [
                'id' => $booking->id,
                'price' => $price,
                'quantity' => 1,
                'name' => "Court {$booking->court->name} ({$booking->starts_at->format('H:i')} - {$booking->ends_at->format('H:i')})",
            ];
        })->toArray();

        $expectedTotal = array_sum(array_column($itemDetails, 'price'));

        $snapUrl = $this->midtransService->generateSnapUrl(
            $payment->uuid,
            $expectedTotal,
            $invoice->customer_name,
            $invoice->customer_phone,
            $itemDetails,
            $callbackFinishUrl,
            $callbackErrorUrl,
        );

        Cache::put("snap_{$payment->uuid}", $snapUrl, now()->addMinutes(5)); //ttl

        return $snapUrl;
    }

    protected function createInvoiceFromCart(array $data, $grouped, bool $isWalkIn): BookingInvoice
    {
        $customerName = $data['customer_name'] ?? 'Guest';
        $customerPhone = $data['customer_phone'] ?? '08123456789';

        $customer = Customer::where('phone', $customerPhone)->first();
        $customerId = $customer?->id;

        $invoice = $this->invoiceService->createBookingInvoice([
            'customer_id' => $customerId,
            'customer_name' => $customer?->name ?? $customerName,
            'customer_phone' => $customerPhone,
            'is_walk_in' => $isWalkIn,
            'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
            'created_by_id' => filament()->auth()->id(),
        ]);

        $invoiceTotal = 0;

        foreach ($grouped as $group) {
            $booking = $this->createBookingFromSlotGroup($group, $customer, $customerName, $customerPhone, $invoice);
            $invoiceTotal += $booking->total_price;
        }

        $invoice->update(['total_amount' => $invoiceTotal]);
        return $invoice;
    }

    protected function createBookingFromSlotGroup($group, $customer, $customerName, $customerPhone, $invoice): Booking
    {
        $courtId = $group['court_id'];
        $date = $group['date'];
        $startsAt = Carbon::parse($date . $group['start_time']);
        $endsAt = Carbon::parse($date . $group['end_time']);
        $mustCheckInBefore = $startsAt->copy()->addMinutes(15); //ttl

        $booking = Booking::create([
            'uuid' => \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?? $customerName,
            'customer_phone' => $customerPhone,
            'court_id' => $courtId,
            'date' => $date,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'must_check_in_before' => $mustCheckInBefore,
            'status' => 'held',
            'attendance_status' => 'pending',
            'booking_invoice_id' => $invoice->id,
            'created_by_type' => filament()->auth()->user() ? filament()->auth()->user()::class : null,
            'created_by_id' => filament()->auth()->id(),
        ]);

        $total = $this->createSlotsAndCalculateBookingTotal($booking, $courtId, $date, $startsAt, $endsAt);
        $booking->update(['total_price' => $total]);

        return $booking;
    }

    protected function calculatePaymentAmount(float $totalAmount, bool $isPaidInFull): float
    {
        return $isPaidInFull ? $totalAmount : (int) round($totalAmount / 2, -2);
    }

    protected function createSlotsAndCalculateBookingTotal(Booking $booking, int $courtId, string $date, Carbon $startsAt, Carbon $endsAt): float
    {
        $bookingTotal = 0;
        $hour = $startsAt->copy();

        while ($hour < $endsAt) {
            $pricingRule = $this->pricingRulesService->gerPricingRuleForHour($courtId, $date, $hour);

            BookingSlot::create([
                'booking_id' => $booking->id,
                'court_id' => $courtId,
                'slot_start' => $hour,
                'slot_end' => $hour->copy()->addHour(),
                'status' => 'held',
                'price' => $pricingRule->price_per_hour,
                'pricing_rule_id' => $pricingRule->id,
            ]);

            $bookingTotal += $pricingRule->price_per_hour;
            $hour->addHour();
        }

        return $bookingTotal;
    }
}
