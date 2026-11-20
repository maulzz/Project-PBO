<?php

use App\Models\Pesanan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/admin/pesanans/{pesanan}/struk', function (Pesanan $pesanan) {
        abort_unless(auth()->user()?->isPelayan(), 403);

        $pesanan->load(['meja', 'pelayan', 'pembayaran', 'detailPesanans.menu', 'detailPesanans.condiments']);

        return view('print.struk-pesanan', ['pesanan' => $pesanan]);
    })->name('pesanan.print.struk');

    Route::get('/admin/pesanans/{pesanan}/tiket-dapur', function (Pesanan $pesanan) {
        abort_unless(auth()->user()?->isPelayan(), 403);

        $pesanan->load(['meja', 'detailPesanans.menu', 'detailPesanans.condiments']);

        return view('print.tiket-dapur', ['pesanan' => $pesanan]);
    })->name('pesanan.print.tiket-dapur');
});
