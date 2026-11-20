<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class Pemilik extends Pengguna
{
    protected static function booted(): void
    {
        static::addGlobalScope('pemilik', function (Builder $builder): void {
            $builder->where('role', 'pemilik');
        });
    }

    /**
     * @return array{stok_menipis: int, pesanan_aktif: int, pendapatan_hari_ini: float}
     */
    public function lihatDashboard(): array
    {
        return [
            'stok_menipis' => Menu::where('stok', '<=', 5)->count(),
            'pesanan_aktif' => Pesanan::whereIn('status_pesanan', ['Pesanan Masuk', 'Diterima', 'Diproses'])->count(),
            'pendapatan_hari_ini' => (float) Pembayaran::where('status_pembayaran', 'lunas')
                ->whereDate('waktu_bayar', today())
                ->sum('total_bayar'),
        ];
    }

    /**
     * @return array{total_transaksi: int, total_pendapatan: float}
     */
    public function lihatRingkasanTransaksi(): array
    {
        return [
            'total_transaksi' => Pesanan::where('status_pesanan', '!=', 'Dibatalkan')->count(),
            'total_pendapatan' => (float) Pembayaran::where('status_pembayaran', 'lunas')->sum('total_bayar'),
        ];
    }
}
