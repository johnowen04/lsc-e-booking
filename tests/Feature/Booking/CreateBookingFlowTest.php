<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\CourtSlotAvailabilityService;
use App\Services\InvoiceService;
use App\Services\PricingRuleService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\Traits\BuildsBookingSlots;
use Tests\TestCase;

class CreateBookingFlowTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBookingSlots;

    public function test_create_booking_flow_creates_invoice_bookings_slots_and_payment()
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::factory()->create();
        $court = Court::first();
        $date = '2025-07-06';
        $startTime = '08:00:00';
        $endTime = '10:00:00';

        // Ensure court schedule slots exist
        app(\App\Services\CourtScheduleSlotGeneratorService::class)
            ->generateSlotsForCourtAndDate($court, Carbon::parse($date));

        $formData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'johndoe@example.com',
            'customer_phone' => '08123456789',
            'is_paid_in_full' => true,
            'payment_method' => PaymentMethod::CASH->value,
        ];

        $groupedSlots = [[
            'court_id' => $court->id,
            'date' => $date,
            'starts_at' => $startTime,
            'ends_at' => $endTime,
            'slots' => $this->buildSlots($court, $date, $startTime, $endTime),
        ]];

        $flow = new CreateBookingFlow(
            app(CourtSlotAvailabilityService::class),
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PricingRuleService::class),
            app(PaymentProcessor::class),
        );

        $payment = $flow->execute(
            formData: $formData,
            groupedSlots: collect($groupedSlots),
            options: [
                'creator' => $user,
                'is_walk_in' => true,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        $start = Carbon::parse("{$date} {$startTime}");
        $end = Carbon::parse("{$date} {$endTime}");
        $expectedSlotCount = $start->diffInHours($end);

        $this->assertDatabaseCount('bookings', 1);
        $this->assertDatabaseCount('booking_slots', $expectedSlotCount);
        $this->assertDatabaseCount('booking_invoices', 1);
        $this->assertDatabaseCount('payments', 1);

        $this->assertEquals('paid', $payment->status);
        $this->assertEquals(PaymentMethod::CASH->value, $payment->method);

        // Verify slot prices
        $pricingService = app(PricingRuleService::class);
        $invoice = $payment->paymentables->first()->paymentable->refresh();
        $booking = $invoice->bookings->first();

        foreach ($booking->slots as $slot) {
            $expectedPrice = $pricingService->getPriceForHour(
                $slot->court_id,
                $slot->start_at->toDateString(),
                $slot->start_at->copy()
            );

            $this->assertEquals($expectedPrice, $slot->price);
        }
    }
}
