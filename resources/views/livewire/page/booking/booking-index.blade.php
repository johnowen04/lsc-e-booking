<div class="space-y-4">
    @forelse ($bookings as $booking)
        <a href="{{ \App\Filament\Customer\Pages\Booking\BookingShow::getUrl(['uuid' => $booking->uuid]) }}"
            class="block p-5 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 hover:bg-gray-50 dark:hover:bg-gray-800 transition">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">
                    {{ $booking->court->name }} ({{ $booking->booking_number }})
                </h3>
                @php
                    $status = strtolower($booking->status);
                    $invoiceStatus = strtolower($booking->invoice->status ?? 'unpaid');
                    $attendanceStatus = strtolower($booking->attendance_status ?? 'pending');

                    $statusClasses = match ($status) {
                        'confirmed' => 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100',
                        'cancelled' => 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-100',
                        'expired' => 'bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-100',
                        default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100',
                    };

                    $invoiceClasses = match ($invoiceStatus) {
                        'partially_paid' => 'bg-info-100 dark:bg-info-800 text-info-800 dark:text-info-100',
                        'paid' => 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100',
                        'cancelled' => 'bg-red-100 dark:bg-red-800 text-red-800 dark:text-red-100',
                        'expired' => 'bg-yellow-100 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-100',
                        default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-100',
                    };

                    $attendanceClasses = match ($attendanceStatus) {
                        'attended' => 'bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100',
                        'no-show' => 'bg-danger-100 dark:bg-danger-700 text-danger-800 dark:text-danger-100',
                        default => 'bg-info-100 dark:bg-info-700 text-info-800 dark:text-info-100',
                    };
                @endphp
                <div>
                    <span class="inline-block text-sm px-2 py-1 rounded font-medium {{ $invoiceClasses }}">
                        {{ \Illuminate\Support\Str::headline($booking->invoice->status) }}
                    </span>
                    <span class="inline-block text-sm px-2 py-1 rounded font-medium {{ $statusClasses }}">
                        {{ \Illuminate\Support\Str::headline($booking->status) }}
                    </span>
                    <span class="inline-block text-sm px-2 py-1 rounded font-medium {{ $attendanceClasses }}">
                        {{ \Illuminate\Support\Str::headline($booking->attendance_status) }}
                    </span>
                </div>
            </div>

            <div class="text-sm text-gray-600 dark:text-gray-300">
                <p>Date: <strong>{{ $booking->date->format('d M Y') }}</strong></p>
                <p>Time: {{ $booking->starts_at->format('H:i') }} - {{ $booking->ends_at->format('H:i') }}</p>
            </div>
        </a>
    @empty
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 py-10">
            <p>You don't have any bookings yet.</p>
            <p class="mt-2">
                <a href="{{ \App\Filament\Customer\Pages\Booking\BookingSchedule::getUrl() }}"
                    class="text-blue-600 hover:underline font-medium">
                    Make your first booking →
                </a>
            </p>
        </div>
    @endforelse

    @if ($bookings->hasPages())
        <div class="mt-6">
            {{ $bookings->links() }}
        </div>
    @endif
</div>
