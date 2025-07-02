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

    private function makeHourSlot(string $hour): array
    {
        $start = Carbon::parse($hour)->startOfHour();
        return [
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'must_check_in_before' => $start->copy()->addMinutes(15),
        ];
    }

    public function test_it_marks_unattended_bookings_as_no_show_after_grace_period()
    {
        // Set current time to 08:30
        Carbon::setTestNow(Carbon::parse('2025-07-02 08:30:00'));

        // 🟥 Overdue booking: must_check_in_before = 08:15 → now is 08:30 → should be no_show
        $overdueBooking = Booking::factory()->create(array_merge(
            $this->makeHourSlot('2025-07-02 08:00'),
            ['status' => 'confirmed', 'attendance_status' => 'pending', 'checked_in_at' => null]
        ));

        // 🟩 Not yet overdue: starts at 09:00 → must_check_in_before = 09:15 → now is 08:30 → too early to mark
        $notYetDue = Booking::factory()->create(array_merge(
            $this->makeHourSlot('2025-07-02 09:00'),
            ['status' => 'confirmed', 'attendance_status' => 'pending', 'checked_in_at' => null]
        ));

        // 🟩 Already attended: should not be touched
        $alreadyCheckedIn = Booking::factory()->create(array_merge(
            $this->makeHourSlot('2025-07-02 08:00'),
            ['status' => 'confirmed', 'attendance_status' => 'attended', 'checked_in_at' => now()->subMinutes(10)]
        ));

        // 🟩 Already marked no_show: should stay the same
        $alreadyNoShow = Booking::factory()->noShow()->create($this->makeHourSlot('2025-07-02 07:00'));

        // 🟩 Already cancelled: should stay the same
        $alreadyCancelled = Booking::factory()->cancelled()->create($this->makeHourSlot('2025-07-02 07:00'));

        // Run the job
        (new CleanupUnattendedBookings())->handle();

        // Refresh all
        $overdueBooking->refresh();
        $notYetDue->refresh();
        $alreadyCheckedIn->refresh();
        $alreadyNoShow->refresh();
        $alreadyCancelled->refresh();

        // ✅ Assert only the overdue booking is marked as no_show
        $this->assertEquals('no_show', $overdueBooking->status);
        $this->assertEquals('no_show', $overdueBooking->attendance_status);

        $this->assertEquals('confirmed', $notYetDue->status);
        $this->assertEquals('pending', $notYetDue->attendance_status);

        $this->assertEquals('confirmed', $alreadyCheckedIn->status);
        $this->assertEquals('attended', $alreadyCheckedIn->attendance_status);

        $this->assertEquals('no_show', $alreadyNoShow->status);
        $this->assertEquals('no_show', $alreadyNoShow->attendance_status);

        $this->assertEquals('cancelled', $alreadyCancelled->status);
        $this->assertEquals('cancelled', $alreadyCancelled->attendance_status);
    }
}
