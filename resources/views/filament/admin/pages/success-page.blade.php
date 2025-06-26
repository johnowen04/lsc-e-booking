<x-filament::page>
    <div class="w-full h-[calc(100vh-6rem)] flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-6 space-y-6">
            <div class="text-center no-print">
                <h1 class="text-3xl font-bold text-success-600 dark:text-success-400">Thank You!</h1>
                @if ($invoice->paid_amount > 0)
                    <p class="mt-2 text-gray-700 dark:text-gray-200">
                        We've received your booking and your payment is being processed.
                    </p>
                @else
                    <p class="mt-2 text-gray-700 dark:text-gray-200">
                        We have received your payment, please wait for the receipt. Refresh page after 3 seconds if not
                        automatically updated.
                    </p>
                @endif

                <div class="pt-4 text-center flex justify-between items-center gap-2">
                    <x-filament::button tag="a" icon="heroicon-o-eye"
                        href="{{ App\Filament\Admin\Resources\BookingInvoiceResource::getUrl('view', ['record' => $invoice]) }}">
                        View Booking Invoice Detail
                    </x-filament::button>

                    @if ($invoice->paid_amount > 0)
                        <x-filament::button icon="heroicon-o-printer" color="gray" onclick="window.print()">
                            Print Receipt
                        </x-filament::button>
                    @else
                        <x-filament::button icon="heroicon-o-arrow-path" color="gray"
                            onclick="window.location.reload()">
                            Refresh Page
                        </x-filament::button>
                    @endif
                </div>
            </div>

            @if ($invoice->paid_amount > 0)
                <div class="border-t border-gray-200 dark:border-gray-700 pt-4 text-sm space-y-2">
                    <span class="font-bold text-center block mb-2 text-base no-print">Transaction Details</span>
                    <div class="receipt-area">
                        <div class="receipt-header print-only hidden text-center mb-4">
                            <h1 class="text-lg font-bold">Lamongan Sports Center</h1>
                            <p class="text-sm">Jalan Kusuma Negara No.3, Lamongan</p>
                        </div>
                        <div class="text-gray-800 dark:text-gray-200 receipt-page">
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-bold">Payment ID</span>
                                <span class="break-all">{{ $payment->uuid }}</span>
                            </div>
                            <br>
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-medium">Payment Date</span>
                                <span>{{ $payment->created_at->format('d M Y H:i:s') }}</span>
                            </div>
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-medium">Payment Method</span>
                                <span>{{ \App\Enums\PaymentMethod::from($payment->method)->label() }}</span>
                            </div>
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-medium">Paid Amount</span>
                                <span>Rp{{ number_format($payment->paid_amount) }}</span>
                            </div>
                            <br>
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-bold">Invoice ID</span>
                                <span class="break-all">{{ $invoice->uuid }}</span>
                            </div>
                            <br>
                            @if ($invoice->paid_amount < $invoice->total_amount)
                                <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                    <span class="font-medium">Remaining Amount</span>
                                    <span>Rp{{ number_format($invoice->total_amount - $invoice->paid_amount) }}</span>
                                </div>
                            @endif
                            <div class="flex justify-between text-gray-800 dark:text-gray-200">
                                <span class="font-medium">Total Amount</span>
                                <span>Rp{{ number_format($invoice->total_amount) }}</span>
                            </div>
                            @if ($invoice->paid_amount < $invoice->total_amount)
                                <div class="text-gray-800 dark:text-gray-200 print-only">
                                    <br>
                                    <span class="font-bold text-center block">This invoice is partially paid.</span>
                                    <span class="font-bold text-center block">Pay remaining amount on your first booking
                                        session.</span>
                                </div>
                            @else
                                <div class="text-gray-800 dark:text-gray-200 print-only">
                                    <br>
                                    <span class="font-bold text-center block">This invoice has been fully
                                        paid.</span>
                                    <span class="font-bold text-center block">This is a valid booking
                                        confirmation.</span>
                                </div>
                            @endif
                            <div class="text-gray-800 dark:text-gray-200 print-only">
                                <br>
                                <span class="font-bold text-center block">Thank You!</span>
                            </div>
                        </div>

                        <span class="font-bold text-center block mb-2 text-base pt-2 no-print">Booking Details</span>

                        <div class="text-gray-800 dark:text-gray-200">
                            @foreach ($invoice->bookings as $booking)
                                <div class="block space-y-4 receipt-page">
                                    <div class="mt-3 text-center hidden">
                                        <svg class="booking-barcode mx-auto"
                                            data-code="{{ $booking->booking_number ?? $booking->id }}"></svg>
                                        <span class="text-xs text-gray-500">Booking Number:
                                            {{ $booking->booking_number ?? $booking->id }}</span>
                                    </div>
                                    <div
                                        class="booking-box rounded-lg border border-gray-200 dark:border-gray-700 p-4 bg-gray-50 dark:bg-gray-800">
                                        <div class="flex justify-between mb-1">
                                            <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Booking
                                                Number</span>
                                            <span class="text-sm font-semibold">{{ $booking->booking_number }}</span>
                                        </div>
                                        <div class="flex justify-between mb-1">
                                            <span
                                                class="text-sm text-gray-600 dark:text-gray-400 font-medium">Court</span>
                                            <span class="text-sm font-semibold">{{ $booking->court->name }}</span>
                                        </div>
                                        <div class="flex justify-between mb-1">
                                            <span
                                                class="text-sm text-gray-600 dark:text-gray-400 font-medium">Date</span>
                                            <span class="text-sm">{{ $booking->starts_at->format('l, d M Y') }}</span>
                                        </div>
                                        <div class="flex justify-between mb-2">
                                            <span
                                                class="text-sm text-gray-600 dark:text-gray-400 font-medium">Time</span>
                                            <span class="text-sm">{{ $booking->starts_at->format('H:i') }} -
                                                {{ $booking->ends_at->format('H:i') }}</span>
                                        </div>

                                        {{-- Slot breakdown --}}
                                        @if ($booking->slots->count())
                                            <div
                                                class="text-sm mt-3 border-t border-gray-300 dark:border-gray-700 pt-2 space-y-1 no-print hidden">
                                                @foreach ($booking->slots as $slot)
                                                    <div class="flex justify-between">
                                                        <div>
                                                            (1 slot)
                                                            {{ $slot->slot_start->format('H:i') }} -
                                                            {{ $slot->slot_end->format('H:i') }}
                                                            @if ($slot->pricingRule?->name)
                                                                <span
                                                                    class="text-xs text-gray-500">({{ $slot->pricingRule->name }})</span>
                                                            @endif
                                                        </div>
                                                        <div>Rp{{ number_format($slot->price) }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($booking->slots->count())
                                            @foreach ($booking->slotsGroupedByPricingRule() as $groupName => $group)
                                                <div
                                                    class="text-sm mt-3 border-t border-gray-300 dark:border-gray-700 pt-2 space-y-1">
                                                    <div class="flex justify-between">
                                                        <div>
                                                            {{ $group['slots']->count() }}x
                                                            {{ $groupName }}
                                                        </div>
                                                        <div>Rp{{ number_format($group['price']) }}</div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif

                                        <div
                                            class="flex justify-between mt-3 border-t border-gray-300 dark:border-gray-700 pt-2">
                                            <span
                                                class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total</span>
                                            <span
                                                class="text-sm font-semibold">Rp{{ number_format($booking->total_price) }}</span>
                                        </div>

                                        <div class="text-gray-800 dark:text-gray-200 print-only">
                                            <br>
                                            @if ($invoice->paid_amount < $invoice->total_amount)
                                                <span class="font-bold text-center block">This invoice is
                                                    partially paid.</span>
                                            @else
                                                <span class="font-bold text-center block">This invoice has been
                                                    fully
                                                    paid.</span>
                                            @endif
                                            <span class="font-bold text-center block">This is a valid
                                                booking confirmation.</span>
                                        </div>
                                        <div class="text-gray-800 dark:text-gray-200 print-only">
                                            <span class="font-bold text-center block">Thank You!</span>
                                        </div>

                                        <div class="booking-fingerprint text-center pt-2">
                                            <span class="text-xs text-center font-light">{{ $booking->uuid }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('styles')
        <style>
            .print-only {
                display: none;
            }

            @media print {
                @page {
                    size: 80mm 80mm;
                    margin: 5mm;
                }

                @page :first {
                    size: 80mm 120mm;
                    margin: 5mm;
                }

                @page {
                    @bottom-center {
                        font-size: 8pt;
                        font-family: monospace;
                        color: gray;
                        margin-bottom: 4px;
                        content: "Page " counter(page) " of " counter(pages);
                    }
                }

                .print-only {
                    display: block !important;
                }

                .no-print {
                    display: none !important;
                }

                .receipt-area,
                .receipt-area * {
                    visibility: visible !important;
                }

                .receipt-area {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    font-family: monospace;
                    font-size: 9pt;
                    background: white;
                    padding: 0;
                    margin: 0;
                }

                .receipt-area h1,
                .receipt-area h2,
                .receipt-area h3 {
                    font-size: 10pt;
                }

                .receipt-area p,
                .receipt-area span,
                .receipt-area div {
                    font-size: 8pt;
                    line-height: 1.2;
                }

                .receipt-area svg.booking-barcode {
                    width: 100%;
                    max-width: 100%;
                    height: 40px;
                    margin-top: 8px;
                }

                .receipt-header {
                    margin-bottom: 12px;
                }

                .receipt-header h1,
                .receipt-header p {
                    font-family: monospace;
                    font-size: 8pt;
                    line-height: 1.4;
                }

                .booking-box {
                    padding: 0 !important;
                    border: none !important;
                    background: white !important;
                }

                body * {
                    visibility: hidden !important;
                }

                .receipt-page {
                    page-break-after: always;
                }

                .receipt-page:last-child {
                    page-break-after: auto;
                }

                .booking-fingerprint span {
                    font-size: 6pt;
                    color: gray;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.booking-barcode').forEach(el => {
                    JsBarcode(el, el.dataset.code, {
                        format: "CODE128",
                        width: 1.5,
                        height: 40,
                        displayValue: false,
                        margin: 0
                    });
                });
            });
        </script>
    @endpush
</x-filament::page>
