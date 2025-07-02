<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\MidtransService;
use App\Services\PaymentService;
use App\Services\PricingRuleService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingPricingCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_applies_correct_dynamic_price_per_slot_booking_invoice_and_payment()
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::factory()->create();
        $court = Court::first();

        $date = '2025-07-03'; // Thursday
        $startTime = '08:00:00';
        $endTime = '10:00:00'; // 2 hours

        $formData = [
            'customer_name' => 'Jane Pricing',
            'customer_phone' => '08123456789',
            'is_paid_in_full' => true,
            'payment_method' => PaymentMethod::CASH->value,
        ];

        $groupedSlots = [[
            'court_id' => $court->id,
            'date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]];

        $flow = new CreateBookingFlow(
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PaymentService::class),
            app(MidtransService::class),
        );

        $payment = $flow->execute(
            $formData,
            $groupedSlots,
            customer: null,
            options: [
                'created_by_type' => $user::class,
                'created_by_id' => $user->id,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        $invoice = $payment->paymentables->first()?->paymentable->refresh();
        $booking = $invoice->bookings->first();
        $slots = $booking->slots;

        $this->assertNotEmpty($slots);

        $pricingService = app(PricingRuleService::class);

        $totalCalculated = 0;

        foreach ($slots as $slot) {
            $price = $pricingService->getPriceForHour(
                courtId: $slot->court_id,
                date: $slot->start_at->toDateString(),
                hour: $slot->start_at->copy()
            );

            $this->assertEquals($price, $slot->price, "Slot at {$slot->start_at->format('H:i')} should be priced at {$price}");
            $totalCalculated += $price;
        }

        $this->assertEquals($totalCalculated, $booking->total_price);
        $this->assertEquals($totalCalculated, $invoice->total_amount);
        $this->assertEquals($totalCalculated, $payment->amount);
    }
}
