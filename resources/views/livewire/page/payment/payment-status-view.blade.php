<div>
    <link rel="stylesheet" href="{{ asset('css/livewire-component/theme.css') }}">
    @if ($statusCode === null)
        <div class="flex items-center justify-center min-h-screen text-center px-4">
            <div>
                <svg class="animate-spin h-8 w-8 text-primary-600 mx-auto mb-4" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>

                <h2 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Redirecting to Payment</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Please wait while we redirect you to the secure payment page.
                </p>
            </div>
        </div>
    @elseif ($statusCode >= 200 && $statusCode < 300)
        <x-payment.success :order-id="$orderId" :status-code="$statusCode" :payment="$payment" :invoice="$invoice" :is-admin="$isAdmin"
            :redirect-url="$redirectUrl" />
    @else
        <x-payment.failed :order-id="$orderId" :status-code="$statusCode" />
    @endif
</div>
