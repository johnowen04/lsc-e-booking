@props(['invoice', 'isAdmin' => false, 'redirectUrl'])

<div x-data="qrModal">
    <div class="max-w-4xl mx-auto p-6 bg-white dark:bg-gray-900 shadow-xl rounded-xl space-y-6">
        <div class="text-center border-b pb-6">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">Booking Invoice</h2>
        </div>

        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-900 p-4 mb-6 rounded shadow">
            <p class="font-medium">
                ⚠️ Harap hadir maksimal 15 menit setelah waktu booking dimulai. Jika tidak, booking Anda akan otomatis
                dibatalkan atau dianggap hangus.
            </p>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
            <div class="flex justify-between items-center">
                <span class="font-medium text-gray-600 dark:text-gray-400">Status:</span>
                <x-filament::badge :color="match ($invoice->status) {
                    'partially_paid' => 'info',
                    'paid' => 'success',
                    'cancelled' => 'danger',
                    'expired' => 'warning',
                    default => 'gray',
                }">
                    {{ \Illuminate\Support\Str::headline($invoice->status) }}
                </x-filament::badge>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Invoice ID:</span>
                <span class="text-right">{{ $invoice->uuid }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Date:</span>
                <span class="text-right">{{ $invoice->created_at->format('l, d M Y, H:i:s') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Customer:</span>
                <span class="text-right">{{ $invoice->customer->name ?? $invoice->customer_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Total Amount:</span>
                <span class="text-right text-gray-900 dark:text-white font-semibold">
                    Rp{{ number_format($invoice->total_amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Paid Amount:</span>
                <span class="text-right text-gray-900 dark:text-white font-semibold">
                    Rp{{ number_format($invoice->paid_amount, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="font-medium text-gray-600 dark:text-gray-400">Remaining Amount:</span>
                <span class="text-right text-gray-900 dark:text-white font-semibold">
                    Rp{{ number_format($invoice->getRemainingAmount(), 0, ',', '.') }}
                </span>
            </div>
        </div>

        <div class="mt-4 text-center">
            <button @click="showQrModal = true"
                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700">
                <x-filament::icon icon="heroicon-o-qr-code" class="w-4 h-4" />
                <span>Show QR Code</span>
            </button>
        </div>

        @if ($isAdmin)
            @if ($invoice->payments->isNotEmpty())
                <div class="mt-8">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Payments</h3>
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                            <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                                <tr>
                                    <th class="px-4 py-2">Date</th>
                                    <th class="px-4 py-2">Method</th>
                                    <th class="px-4 py-2">Reference</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2 text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->payments as $payment)
                                    <tr class="border-b dark:border-gray-700">
                                        <td class="px-4 py-2">
                                            {{ $payment->paid_at->format('D, d M Y H:i:s') }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ \App\Enums\PaymentMethod::from($payment->method)->label() }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ $payment->reference_code ?? '-' }}
                                        </td>
                                        <td>
                                            <x-filament::badge :color="match ($payment->status) {
                                                'pending' => 'warning',
                                                'paid' => 'success',
                                                'failed' => 'danger',
                                                default => 'gray',
                                            }">
                                                {{ \Illuminate\Support\Str::headline($payment->status) }}
                                            </x-filament::badge>
                                        </td>
                                        @if ($payment->status === 'paid')
                                            <td class="px-4 py-2 text-right">
                                                Rp{{ number_format($payment->paid_amount, 0, ',', '.') }}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif

        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">Bookings</h3>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-sm text-left text-gray-600 dark:text-gray-300">
                    <thead class="bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2">Code</th>
                            <th class="px-4 py-2">Court</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Time</th>
                            <th class="px-4 py-2 text-right">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->bookings as $booking)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-2">{{ $booking->booking_number ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ $booking->court->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2">{{ \Carbon\Carbon::parse($booking->date)->format('D, d M Y') }}
                                </td>
                                <td class="px-4 py-2">
                                    {{ \Carbon\Carbon::parse($booking->starts_at)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($booking->ends_at)->format('H:i') }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    Rp{{ number_format($booking->total_price, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-gray-500 dark:text-gray-400">
                                    No bookings found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-end gap-3">
            @if ($redirectUrl)
                <x-filament::button tag="a" href="{{ $redirectUrl }}" color="primary"
                    icon="heroicon-o-arrow-top-right-on-square">
                    View Details
                </x-filament::button>
            @endif

            <x-filament::button color="gray" icon="heroicon-o-printer" onclick="window.print()">
                Print Invoice
            </x-filament::button>
        </div>
    </div>

    <div x-show="showQrModal" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div @click.outside="showQrModal = false"
            class="bg-white dark:bg-gray-900 rounded-xl shadow-lg max-w-sm w-full p-6 space-y-4 text-center">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Invoice QR Code</h2>
            <canvas id="invoice-qr" class="mx-auto"></canvas>
            <x-filament::button color="gray" @click="showQrModal = false">Close</x-filament::button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('qrModal', () => ({
                    showQrModal: false,
                    uuid: @json($invoice->uuid),
                    init() {
                        this.$watch('showQrModal', (value) => {
                            if (value) {
                                new QRious({
                                    element: document.getElementById('invoice-qr'),
                                    value: this.uuid,
                                    size: 200,
                                });
                            }
                        });
                    },
                }));
            });
        </script>
    @endpush
@endonce
