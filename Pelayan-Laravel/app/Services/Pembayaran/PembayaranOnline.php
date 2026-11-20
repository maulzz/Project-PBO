<?php

namespace App\Services\Pembayaran;

use App\Contracts\MetodePembayaran;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;

class PembayaranOnline implements MetodePembayaran
{
    public function __construct(
        protected Pesanan $pesanan,
        protected ?string $transaksiRef = null,
        protected ?int $dikonfirmasiOleh = null,
    ) {}

    public function getNamaMetode(): string
    {
        return 'online';
    }

    public function prosesPembayaran(float $totalBayar): bool
    {
        $this->pesanan->pembayaran()->updateOrCreate(
            ['pesanan_id' => $this->pesanan->id],
            [
                'metode_pembayaran' => $this->getNamaMetode(),
                'total_bayar' => $totalBayar,
                'status_pembayaran' => 'lunas',
                'transaksi_ref' => $this->transaksiRef ?: ($this->pesanan->pembayaran?->transaksi_ref ?? 'ONLINE-'.$this->pesanan->nomor_pesanan),
                'waktu_bayar' => now(),
                'dikonfirmasi_oleh' => $this->dikonfirmasiOleh ?? Auth::id(),
            ],
        );

        return true;
    }
}
