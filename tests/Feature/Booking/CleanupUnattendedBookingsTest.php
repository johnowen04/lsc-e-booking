<?php

namespace Tests\Feature\Booking;

use App\Jobs\CleanupUnattendedBookings;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CleanupUnattendedBookingsTest extends TestCase
{
    use RefreshDatabase;

    private function hourSlot(string $hour): array
    {
        $start = Carbon::parse($hour)->startOfHour();

        return [
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'must_check_in_before' => $start->copy()->addMinutes(15),
        ];
    }

    public function test_unattended_bookings_are_marked_no_show_after_grace_period(): void
    {
        Carbon::setTestNow('2025-07-02 08:30:00');

        $overdueBooking = Booking::factory()->create([
            ...$this->hourSlot('2025-07-02 08:00'),
            'status' => 'confirmed',
            'attendance_status' => 'pending',
            'attended_at' => null,
        ]);

        $notYetDue = Booking::factory()->create([
            ...$this->hourSlot('2025-07-02 09:00'),
            'status' => 'confirmed',
            'attendance_status' => 'pending',
            'attended_at' => null,
        ]);

        $alreadyCheckedIn = Booking::factory()->create([
            ...$this->hourSlot('2025-07-02 08:00'),
            'status' => 'confirmed',
            'attendance_status' => 'attended',
            'attended_at' => now()->subMinutes(10),
        ]);

        $alreadyNoShow     = Booking::factory()->noShow()->create($this->hourSlot('2025-07-02 07:00'));
        $alreadyCancelled  = Booking::factory()->cancelled()->create($this->hourSlot('2025-07-02 07:00'));
        $alreadyExpired    = Booking::factory()->expired()->create($this->hourSlot('2025-07-02 07:00'));

        (new CleanupUnattendedBookings())->handle();

        $this->assertSame('no_show', $overdueBooking->fresh()->attendance_status);
        $this->assertSame('pending', $notYetDue->fresh()->attendance_status);
        $this->assertSame('attended', $alreadyCheckedIn->fresh()->attendance_status);
        $this->assertSame('no_show', $alreadyNoShow->fresh()->attendance_status);
        $this->assertSame('pending', $alreadyCancelled->fresh()->attendance_status);
        $this->assertSame('pending', $alreadyExpired->fresh()->attendance_status);

        // Ensure status field remains unchanged
        $this->assertSame('confirmed', $overdueBooking->status);
        $this->assertSame('confirmed', $notYetDue->status);
        $this->assertSame('confirmed', $alreadyCheckedIn->status);
        $this->assertSame('confirmed', $alreadyNoShow->status);
        $this->assertSame('cancelled', $alreadyCancelled->status);
        $this->assertSame('expired', $alreadyExpired->status);
    }
}
