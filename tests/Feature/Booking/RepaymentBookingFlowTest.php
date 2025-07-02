<?php

namespace Tests\Feature\Booking;

use App\Actions\Booking\CreateBookingFlow;
use App\Actions\Booking\RepaymentBookingFlow;
use App\Enums\PaymentMethod;
use App\Models\Court;
use App\Models\User;
use App\Services\BookingService;
use App\Services\BookingSlotService;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\MidtransService;
use Database\Seeders\TestDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepaymentBookingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_allows_booking_invoice_repayment()
    {
        $this->seed(TestDatabaseSeeder::class);

        $user = User::factory()->create();
        $court = Court::first();

        // Booking details (2 hours)
        $date = '2025-07-03';
        $start = '08:00:00';
        $end = '10:00:00';

        $groupedSlots = [
            [
                'court_id' => $court->id,
                'date' => $date,
                'start_time' => $start,
                'end_time' => $end,
            ]
        ];

        $createFlow = new CreateBookingFlow(
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PaymentService::class),
            app(MidtransService::class),
        );

        $initialPayment = $createFlow->execute(
            formData: [
                'customer_name' => 'Partial Payer',
                'customer_phone' => '08123456789',
                'is_paid_in_full' => false,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            groupedSlots: $groupedSlots,
            customer: null,
            options: [
                'created_by_type' => $user::class,
                'created_by_id' => $user->id,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        $invoice = $initialPayment->invoice();
        $invoice->refresh();
        $initialAmount = $invoice->total_amount;
        $remainingAmount = $initialAmount - $initialPayment->amount;

        // Confirm invoice is not yet fully paid
        $this->assertEquals('partially_paid', $invoice->status);
        $this->assertGreaterThan(0, $remainingAmount);

        // Execute the repayment
        $repayFlow = new RepaymentBookingFlow(
            app(BookingService::class),
            app(BookingSlotService::class),
            app(InvoiceService::class),
            app(PaymentService::class),
            app(MidtransService::class),
        );

        $secondPayment = $repayFlow->execute(
            formData: [
                'amount' => $remainingAmount,
                'payment_method' => PaymentMethod::CASH->value,
            ],
            invoice: $invoice,
            options: [
                'created_by_type' => $user::class,
                'created_by_id' => $user->id,
                'callback_class' => \App\Filament\Admin\Pages\Payment\PaymentStatus::class,
            ]
        );

        $invoice->refresh();

        $this->assertEquals($initialAmount, $initialPayment->amount + $secondPayment->amount);
        $this->assertEquals('paid', $invoice->status);

        $this->assertDatabaseCount('payments', 2);
        $this->assertEquals(PaymentMethod::CASH->value, $secondPayment->method);
        $this->assertEquals('paid', $secondPayment->status);
    }
}
