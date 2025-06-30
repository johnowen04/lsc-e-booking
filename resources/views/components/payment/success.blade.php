@props(['orderId', 'statusCode', 'payment', 'invoice', 'isAdmin' => false, 'redirectUrl' => null])

<div class="no-print">
    <div class="max-w-4xl mx-auto mt-10 p-6 bg-green-50 border border-green-200 rounded-xl shadow-lg text-green-900">
        <div class="flex items-center gap-3 mb-4">
            <x-heroicon-o-check-circle class="w-6 h-6 text-green-600" />
            <h2 class="text-2xl font-semibold">Payment Successful</h2>
        </div>

        <div class="text-sm space-y-1 mb-6">
            <p>
                Your order <strong>#{{ $orderId }}</strong> has been successfully processed.
            </p>
            <p>
                Status Code: <code class="bg-green-100 px-1 py-0.5 rounded text-green-800">{{ $statusCode }}</code>
            </p>
        </div>
    </div>

    <br>

    <x-invoice.summary :invoice="$invoice" :is-admin="$isAdmin" :redirect-url="$redirectUrl" />
</div>

<div class="print-only">
    <x-invoice.receipt :invoice="$invoice" :payment="$payment" />
</div>
