<?php

use App\Filament\Admin\Pages\ErrorPage;
use App\Filament\Admin\Pages\SuccessPage;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/admin/success', SuccessPage::class)->name('midtrans.success');
Route::get('/admin/error', ErrorPage::class)->name('midtrans.error');

Route::get('/test-midtrans', function () {
    try {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $url = \Midtrans\Snap::getSnapUrl([
            'transaction_details' => [
                'order_id' => 'ORDER-' . time(),
                'gross_amount' => 10000,
            ],
            'customer_details' => [
                'first_name' => 'John',
                'phone' => '08111222333',
            ],
        ]);

        return $url;
    } catch (\Exception $e) {
        return 'ERROR: ' . $e->getMessage();
    }
});
