<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Booking;
use App\Models\BookingSlot;
use App\Models\Customer;
use App\Models\BookingInvoice;
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

    public function createInvoiceWithBookingsForOnline(array $data): BookingInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = $this->createInvoiceFromCart($data, false);
            $amount = $this->calculatePaymentAmount((float) $invoice->total_amount, $data['is_paid_in_full']);

            $payment = $this->paymentService->createPayment(
                $amount,
                $invoice,
                overrides: [
                    'created_by_type' => auth()->user()::class,
                    'created_by_id' => auth()->id(),
                ]
            );

            $snapUrl = $this->generateSnapUrlForInvoice($invoice, $payment, $data['is_paid_in_full']);
            Cache::put("snap_{$invoice->id}", $snapUrl, now()->addMinutes(5)); //ttl

            session()->forget('booking_cart');

            return $invoice;
        });
    }

    public function createInvoiceWithBookingsForWalkIn(array $data): BookingInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = $this->createInvoiceFromCart($data, true);
            $amount = $this->calculatePaymentAmount((float) $invoice->total_amount, $data['is_paid_in_full']);

            $payment = $this->paymentService->createPayment(
                $amount,
                $invoice,
                overrides: [
                    'created_by_type' => filament()->auth()->user()::class,
                    'created_by_id' => filament()->auth()->id(),
                ],
            );

            if (PaymentMethod::from($data['payment_method']) === PaymentMethod::QRIS) {
                $snapUrl = $this->generateSnapUrlForInvoice($invoice, $payment, $data['is_paid_in_full']);
                Cache::put("snap_admin_{$invoice->id}", $snapUrl, now()->addMinutes(5)); //ttl
            } else if (PaymentMethod::from($data["payment_method"]) === PaymentMethod::CASH) {
                $payment = app(PaymentService::class)->updatePayment(
                    $payment->uuid,
                    (float) $payment->amount,
                    PaymentMethod::CASH->value,
                    'paid',
                    $invoice,
                    paidAt: now()->toDateTimeString(),
                    overrides: [
                        'created_by_type' => filament()->auth()->user()::class,
                        'created_by_id' => filament()->auth()->id(),
                    ],
                );

                $redirectUrl = route('midtrans.success', [
                    'order_id' => $payment->uuid,
                ]);

                Cache::put("cash_{$invoice->id}", $redirectUrl, now()->addMinutes(5)); //ttl
            } else {
                throw ValidationException::withMessages(['payment_method' => 'Invalid payment method selected.']);
            }

            session()->forget('booking_cart');

            return $invoice;
        });
    }

    protected function generateSnapUrlForInvoice(BookingInvoice $invoice, $payment, bool $isPaidInFull): string
    {
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

        return $this->midtransService->generateSnapUrl(
            $payment->uuid,
            $expectedTotal,
            $invoice->customer_name,
            $invoice->customer_phone,
            $itemDetails,
        );
    }

    protected function createInvoiceFromCart(array $data, bool $isWalkIn): BookingInvoice
    {
        $customerName = $data['customer_name'] ?? 'Guest';
        $customerPhone = $data['customer_phone'] ?? '08123456789';

        $customer = Customer::where('phone', $customerPhone)->first();
        $customerId = $customer?->id;

        $invoice = $this->invoiceService->createBookingInvoice([
            'customer_id' => $customerId,
            'customer_name' => $customer?->name ?? $customerName,
            'customer_phone' => $customerPhone,
            'created_by_type' => filament()->auth()->user()::class,
            'created_by_id' => filament()->auth()->id(),
            'is_walk_in' => $isWalkIn,
        ]);

        $cart = session('booking_cart', []);
        $grouped = collect($cart)->groupBy('group_id');
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
        $firstSlot = $group->first();
        $lastSlot = $group->last();

        $courtId = $firstSlot['court_id'];
        $date = $firstSlot['date'];
        $startsAt = Carbon::parse("{$date} {$firstSlot['hour']}:00");
        $endsAt = Carbon::parse("{$date} {$lastSlot['hour']}:00")->addHour();
        $mustCheckInBefore = $startsAt->copy()->addMinutes(15); //ttl

        $booking = Booking::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'customer_id' => $customer?->id,
            'customer_name' => $customer?->name ?? $customerName,
            'customer_phone' => $customerPhone,
            'court_id' => $courtId,
            'date' => $startsAt->toDateString(),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'must_check_in_before' => $mustCheckInBefore,
            'status' => 'held',
            'attendance_status' => 'pending',
            'created_by_type' => filament()->auth()->user()::class,
            'created_by_id' => filament()->auth()->id(),
            'booking_invoice_id' => $invoice->id,
        ]);

        $total = $this->createSlotsAndCalculateBookingTotal($booking, $courtId, $date, $startsAt, $endsAt);
        $booking->update(['total_price' => $total]);

        return $booking;
    }

    public function retrySnapPayment(BookingInvoice $invoice, bool $forceNew = false, bool $isWalkIn = false): string
    {
        $pendingPayment = $invoice->payments()
            ->where('status', 'pending')
            ->latest()
            ->first();
        
        $isPaidInFull = $invoice->status === 'partially_paid' ? false : true;

        if ($pendingPayment && !$forceNew) {
            $status = $this->midtransService->checkTransactionStatus($pendingPayment->uuid);

            if (in_array($status, ['expire', 'cancel', 'failure'])) {
                $pendingPayment->update(['status' => 'failed']);
                $pendingPayment = null;
            }
        }

        if (!$pendingPayment || $forceNew) {
            $pendingPayment = $this->paymentService->createPayment(
                $invoice->getRemainingAmount(),
                $invoice,
                overrides: [
                    'created_by_type' => $isWalkIn ? filament()->auth()->user()::class : auth()->user()::class,
                    'created_by_id' => $isWalkIn ? filament()->auth()->id() : auth()->id(),
                ],
            );
        }

        $snapUrl = $this->generateSnapUrlForInvoice($invoice, $pendingPayment, $isPaidInFull);

        Cache::put("snap_{$invoice->id}", $snapUrl, now()->addMinutes(5)); //ttl

        return $snapUrl;
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
