<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PricingRuleService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Traits\BuildsBookingSlots;
use Tests\TestCase;

class BookingPricingCalculationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBookingSlots;

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
            'customer_email' => 'janepricing@example.com',
            'customer_phone' => '08123456789',
            'is_paid_in_full' => true,
            'payment_method' => PaymentMethod::CASH->value,
        ];

        $pricingService = app(PricingRuleService::class);

        $groupedSlots = [[
            'court_id' => $court->id,
            'date' => $date,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'slots' => $this->buildSlots($court, $date, $startTime, $endTime),
        ]];

        $flow = new CreateBookingFlow(
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            $pricingService,
            app(PaymentProcessor::class),
        );

        $payment = $flow->execute(
            $formData,
            collect($groupedSlots),
            options: [
                'creator' => $user,
                'is_walk_in' => true,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        $invoice = $payment->paymentables->first()?->paymentable->refresh();
        $booking = $invoice->bookings->first();
        $slots = $booking->slots;

        $this->assertNotEmpty($slots);

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
