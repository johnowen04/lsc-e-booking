<?php

use App\Filament\Admin\Pages\ErrorPage;
use App\Filament\Admin\Pages\SuccessPage;
use Illuminate\Support\Facades\Route;

Route::get('/admin/success', SuccessPage::class)->name('midtrans.success');
Route::get('/admin/error', ErrorPage::class)->name('midtrans.error');

