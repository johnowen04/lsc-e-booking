<div class="max-w-xl mx-auto mt-10 p-6 bg-red-50 border border-red-200 rounded-xl shadow-lg text-red-900">
    <div class="flex items-center gap-3 mb-4">
        <x-heroicon-o-x-circle class="w-6 h-6 text-red-600" />
        <h2 class="text-xl font-semibold">Payment Failed</h2>
    </div>
    <p class="text-sm mb-2">
        Unfortunately, your order <strong>#{{ $orderId }}</strong> could not be processed.
    </p>
    <p class="text-sm mb-2">
        Please contact support.
    </p>
    <p class="text-sm">Status Code: <code>{{ $statusCode }}</code></p>
</div>
