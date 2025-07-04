<div class="p-6 bg-white dark:bg-gray-900 rounded-2xl shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white mb-6">
        Booking Details
    </h2>

    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm text-gray-700 dark:text-gray-200">
        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Court</dt>
            <dd class="mt-1 font-semibold">{{ $booking->court->name }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Date</dt>
            <dd class="mt-1 font-semibold">{{ $booking->date->format('d M Y') }}</dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Time</dt>
            <dd class="mt-1 font-semibold">
                {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}
            </dd>
        </div>

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
            <dt class="font-medium text-gray-500 dark:text-gray-400">Status</dt>
            <dd class="mt-1">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $statusClasses }}">
                    {{ \Illuminate\Support\Str::headline($booking->status) }}
                </span>
            </dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Payment</dt>
            <dd class="mt-1">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $invoiceClasses }}">
                    {{ \Illuminate\Support\Str::headline($booking->invoice->status ?? 'Unpaid') }}
                </span>
            </dd>
        </div>

        <div>
            <dt class="font-medium text-gray-500 dark:text-gray-400">Attendance</dt>
            <dd class="mt-1">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $attendanceClasses }}">
                    {{ \Illuminate\Support\Str::headline($booking->attendance_status) }}
                </span>
            </dd>
        </div>
    </dl>

    <div class="mt-6">
        <a href="{{ \App\Filament\Customer\Pages\Booking\BookingIndex::getUrl() }}"
            class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline">
            ← Back to My Bookings
        </a>
    </div>
</div>
