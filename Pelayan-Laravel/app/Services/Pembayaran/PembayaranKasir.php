<?php

namespace App\Services\Pembayaran;

use App\Contracts\MetodePembayaran;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class PembayaranKasir implements MetodePembayaran
{
    public function __construct(
        protected Pesanan $pesanan,
        protected float $uangDiterima,
        protected ?int $dikonfirmasiOleh = null,
    ) {}

    public function getNamaMetode(): string
    {
        return 'kasir';
    }

    public function getKembalian(): float
    {
        return max(0.0, $this->uangDiterima - (float) $this->pesanan->total_harga);
    }

    public function prosesPembayaran(float $totalBayar): bool
    {
        if ($this->uangDiterima < $totalBayar) {
            throw new InvalidArgumentException('Uang yang diterima kurang dari total tagihan.');
        }

        $this->pesanan->pembayaran()->updateOrCreate(
            ['pesanan_id' => $this->pesanan->id],
            [
                'metode_pembayaran' => $this->getNamaMetode(),
                'total_bayar' => $totalBayar,
                'status_pembayaran' => 'lunas',
                'transaksi_ref' => $this->pesanan->pembayaran?->transaksi_ref ?? 'KASIR-'.$this->pesanan->nomor_pesanan,
                'waktu_bayar' => now(),
                'dikonfirmasi_oleh' => $this->dikonfirmasiOleh ?? Auth::id(),
            ],
        );

        return true;
    }
}
