@props(['invoice', 'payment'])

<div class="receipt-area">
    <div class="receipt-page text-gray-800 dark:text-gray-200">
        <div class="receipt-header text-center mb-4">
            <h1 class="text-lg font-bold">Lamongan Sports Center</h1>
            <p class="text-sm">Jalan Kusuma Negara No.3, Lamongan</p>
        </div>
        <div class="flex justify-between text-gray-800 dark:text-gray-200">
            <span class="font-bold">Payment ID</span>
            <span class="break-all">{{ $payment->uuid }}</span>
        </div>
        <br>
        <div class="flex justify-between text-gray-800 dark:text-gray-200">
            <span class="font-medium">Payment Date</span>
            <span>{{ $payment->paid_at->format('d M Y H:i:s') }}</span>
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
        <div class="flex justify-between text-gray-800 dark:text-gray-200">
            <span class="font-medium">Date</span>
            <span class="break-all">{{ $invoice->created_at->format('d M Y H:i:s') }}</span>
        </div>
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
        <br>
        @if ($invoice->paid_amount < $invoice->total_amount)
            <div class="text-gray-800 dark:text-gray-200">
                <span class="font-bold text-center block">Pay remaining amount on your first booking
                    session.</span>
                <span class="font-bold text-center block">Partially paid. Thank You!</span>
            </div>
        @else
            <div class="text-gray-800 dark:text-gray-200">
                <span class="font-bold text-center block">This is a valid booking
                    confirmation.</span>
                <span class="font-bold text-center block">Fully paid. Thank You!</span>
            </div>
        @endif
        <br>
        <div class="text-gray-800 dark:text-gray-200">
            <span class="font-bold text-center block">
                Harap hadir maksimal 15 menit setelah waktu booking dimulai. Jika tidak, booking Anda akan otomatis
                dibatalkan atau dianggap hangus.
            </span>
        </div>
    </div>

    <div class="text-gray-800 dark:text-gray-200">
        @foreach ($invoice->bookings as $booking)
            <div class="receipt-page block space-y-4">
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
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Court</span>
                        <span class="text-sm font-semibold">{{ $booking->court->name }}</span>
                    </div>
                    <div class="flex justify-between mb-1">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Date</span>
                        <span class="text-sm">{{ $booking->starts_at->format('l, d M Y') }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Time</span>
                        <span class="text-sm">{{ $booking->starts_at->format('H:i') }} -
                            {{ $booking->ends_at->format('H:i') }}</span>
                    </div>

                    {{-- Slot breakdown --}}
                    @if ($booking->slots->count())
                        @foreach ($booking->slotsGroupedByPricingRule() as $groupName => $group)
                            <div class="text-sm mt-3 border-t border-gray-300 dark:border-gray-700 pt-2 space-y-1">
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

                    <div class="flex justify-between mt-3 border-t border-gray-300 dark:border-gray-700 pt-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400 font-medium">Total</span>
                        <span class="text-sm font-semibold">Rp{{ number_format($booking->total_price) }}</span>
                    </div>

                    <br>

                    <div class="text-gray-800 dark:text-gray-200">
                        <span class="font-bold text-center block">This is a valid
                            booking confirmation.</span>
                        <div class="text-center block">
                            @if ($invoice->paid_amount < $invoice->total_amount)
                                <span class="font-bold">Partially paid.</span>
                            @else
                                <span class="font-bold">Fully paid.</span>
                            @endif
                            <span class="font-bold">Thank You!</span>
                        </div>
                    </div>

                    <div class="booking-fingerprint text-center pt-2">
                        <span class="text-xs text-center font-light">{{ $booking->uuid }}</span>
                    </div>
                </div>
            </div>
        @endforeach
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
                position: absolute !important;
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
