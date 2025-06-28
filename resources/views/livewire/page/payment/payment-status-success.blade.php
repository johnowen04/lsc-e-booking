<div class="max-w-xl mx-auto mt-10 p-6 bg-green-50 border border-green-200 rounded-xl shadow-lg text-green-900">
    <div class="flex items-center gap-3 mb-4">
        <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
        <h2 class="text-xl font-semibold">Payment Successful</h2>
    </div>
    <p class="text-sm mb-2">
        Your order <strong>#{{ $orderId }}</strong> has been successfully processed.
    </p>
    <p class="text-sm mb-6">Status Code: <code>{{ $statusCode }}</code></p>

    <div class="text-right">
        <a href="{{ $redirectUrl }}"
            class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded hover:bg-green-700 transition">
            <x-heroicon-o-arrow-right class="w-4 h-4 mr-2" />
            View Booking Summary
        </a>
    </div>
</div>
