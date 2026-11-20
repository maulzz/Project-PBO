<?php

use App\Http\Controllers\Api\V1\MejaController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\MidtransCallbackController;
use App\Http\Controllers\Api\V1\PesananController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/meja/{qrCode}', [MejaController::class, 'showByQrCode'])->name('meja.show');
    Route::get('/kategoris', [MenuController::class, 'categories'])->name('kategoris.index');
    Route::get('/menus', [MenuController::class, 'index'])->name('menus.index');
    Route::post('/pesanans', [PesananController::class, 'store'])->name('pesanans.store');
    Route::get('/pesanans/{nomorPesanan}', [PesananController::class, 'show'])->name('pesanans.show');
    Route::post('/pesanans/{nomorPesanan}/cek-pembayaran', [PesananController::class, 'cekPembayaran'])->name('pesanans.cek-pembayaran');
    Route::post('/midtrans/callback', MidtransCallbackController::class)->name('midtrans.callback');
});
