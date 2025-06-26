<x-filament::page>
    <div class="w-full h-[calc(100vh-6rem)] flex items-center justify-center px-4">
        <div class="w-full max-w-md bg-white dark:bg-gray-900 shadow-xl rounded-2xl p-6 space-y-6">
            <div class="text-center no-print">
                <div class="text-red-500">
                    <x-heroicon-o-x-circle class="w-16 h-16 mx-auto" />
                </div>

                <h1 class="text-3xl font-bold text-red-600 dark:text-red-400 mt-4">Payment Failed</h1>
                <p class="mt-2 text-gray-700 dark:text-gray-200">
                    Unfortunately, your payment did not go through. This may be due to timeout, cancellation, or a
                    technical issue.
                </p>

                @if ($orderId)
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <span class="font-medium">Order ID:</span>
                        <span class="ml-1 font-mono">{{ $orderId }}</span>
                    </p>
                @endif

                <div class="pt-4 flex justify-center items-center flex-wrap gap-2">
                    <x-filament::button tag="a" icon="heroicon-o-eye"
                        href="{{ \App\Filament\Admin\Resources\BookingInvoiceResource::getUrl('view', ['record' => $invoice]) }}"
                        color="gray">
                        View Invoice
                    </x-filament::button>

                    {{-- Implement this later --}}
                    {{-- <x-filament::button tag="a" icon="heroicon-o-arrow-path"
                        href="{{ route('retry.payment', ['invoice' => $invoice->id]) }}" color="danger">
                        Retry Payment
                    </x-filament::button> --}}

                    <x-filament::button tag="a" icon="heroicon-o-list-bullet"
                        href="{{ \App\Filament\Admin\Resources\BookingResource::getUrl() }}" color="primary">
                        Booking List
                    </x-filament::button>
                </div>

                <p class="text-sm text-gray-500 dark:text-gray-400 pt-4">
                    If the issue persists, please contact our support team for assistance.
                </p>
            </div>
        </div>
    </div>
</x-filament::page>
