<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Processors\Payment\PaymentProcessor;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\CourtScheduleSlotGeneratorService;
use App\Services\CourtSlotAvailabilityService;
use App\Services\InvoiceService;
use App\Services\PricingRuleService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Traits\BuildsBookingSlots;
use Tests\TestCase;
use Carbon\Carbon;

class BookingSlotConflictTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBookingSlots;

    public function test_overlapping_booking_is_rejected()
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::factory()->create();
        $court = Court::first();
        $date = '2025-07-05';

        // Ensure schedule slots are generated
        app(CourtScheduleSlotGeneratorService::class)->generateSlotsForCourtAndDate(
            $court,
            Carbon::parse($date)
        );

        $flow = new CreateBookingFlow(
            app(CourtSlotAvailabilityService::class),
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PricingRuleService::class),
            app(PaymentProcessor::class),
        );

        // First booking: 08:00–10:00
        $flow->execute(
            formData: [
                'customer_name' => 'Alice',
                'customer_email' => 'alice@example.com',
                'customer_phone' => '0811111111',
                'is_paid_in_full' => true,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            groupedSlots: collect([[
                'court_id' => $court->id,
                'date' => $date,
                'starts_at' => '08:00:00',
                'ends_at' => '10:00:00',
                'slots' => $this->buildSlots($court, $date, '08:00:00', '10:00:00'),
            ]]),
            options: [
                'creator' => $user,
                'is_walk_in' => true,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        // Second booking: 09:00–11:00 (overlaps with previous)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Slot for court={$court->id} at {$date} 09:00 is not available.");

        $flow->execute(
            formData: [
                'customer_name' => 'Bob',
                'customer_email' => 'bob@example.com',
                'customer_phone' => '0822222222',
                'is_paid_in_full' => true,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            groupedSlots: collect([[
                'court_id' => $court->id,
                'date' => $date,
                'starts_at' => '09:00:00',
                'ends_at' => '11:00:00',
                'slots' => $this->buildSlots($court, $date, '09:00:00', '11:00:00'),
            ]]),
            options: [
                'creator' => $user,
                'is_walk_in' => true,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );
    }
}
