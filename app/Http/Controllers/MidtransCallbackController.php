<?php

namespace App\Http\Controllers;

use App\Jobs\HandleMidtransCallback;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        try {
            Log::info('📦 Midtrans raw payload:', $request->all());

            $orderId = $request->input('order_id');
            $statusCode = $request->input('status_code');
            $grossAmount = $request->input('gross_amount');
            $signatureKey = $request->input('signature_key');

            $serverKey = config('midtrans.server_key');
            $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

            if ($signatureKey !== $expectedSignature) {
                Log::warning('🚨 Invalid Midtrans signature!', compact('expectedSignature', 'signatureKey'));
                return response()->json(['message' => 'Invalid signature.'], 403);
            }

            Log::info('📦 Valid signature. Initializing Midtrans notification...');
            $notification = app(MidtransService::class)->handleNotification();

            dispatch(new HandleMidtransCallback($notification));

            return response()->json(['message' => 'Callback received'], 200);
        } catch (\Throwable $e) {
            Log::error('❌ Midtrans callback error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Still return 200 to prevent Midtrans retry loop
            return response()->json(['message' => 'Callback error (ignored)'], 200);
        }
    }
}
