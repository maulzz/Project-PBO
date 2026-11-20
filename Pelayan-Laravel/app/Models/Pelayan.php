<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Pelayan extends Pengguna
{
    protected static function booted(): void
    {
        static::addGlobalScope('pelayan', function (Builder $builder): void {
            $builder->where('role', 'pelayan');
        });
    }

    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class, 'pelayan_id');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function kelolaMenu(Menu $menu, array $data): bool
    {
        return $menu->update($data);
    }

    /**
     * Menerima pesanan masuk, memvalidasi & mengurangi stok, serta menandai meja terisi.
     */
    public function terimaPesanan(Pesanan $pesanan): bool
    {
        $pesanan->loadMissing(['detailPesanans.menu', 'meja']);

        $stokKurang = $pesanan->detailPesanans
            ->filter(fn (DetailPesanan $detail): bool => $detail->menu->stok < $detail->jumlah)
            ->map(fn (DetailPesanan $detail): string => "{$detail->menu->nama_menu} (sisa {$detail->menu->stok})");

        if ($stokKurang->isNotEmpty()) {
            throw new RuntimeException('Stok tidak mencukupi: '.$stokKurang->implode(', '));
        }

        return DB::transaction(function () use ($pesanan): bool {
            foreach ($pesanan->detailPesanans as $detail) {
                $detail->menu->kurangiStok($detail->jumlah);
            }

            $pesanan->update([
                'status_pesanan' => 'Diterima',
                'pelayan_id' => $this->id,
            ]);

            $pesanan->meja?->update(['status' => 'terisi']);

            return true;
        });
    }

    /**
     * Memperbarui status pesanan dan menyinkronkan status meja secara otomatis.
     */
    public function ubahStatusPesanan(Pesanan $pesanan, string $statusBaru): bool
    {
        return DB::transaction(function () use ($pesanan, $statusBaru): bool {
            if ($statusBaru === 'Dibatalkan' && $pesanan->status_pesanan === 'Diterima') {
                foreach ($pesanan->detailPesanans()->with('menu')->get() as $detail) {
                    $detail->menu->increment('stok', $detail->jumlah);
                }
            }

            $updateData = ['status_pesanan' => $statusBaru];

            if ($statusBaru === 'Dibatalkan') {
                $updateData['dibatalkan_oleh'] = $this->id;
                $updateData['waktu_dibatalkan'] = now();
            }

            $pesanan->update($updateData);

            if ($statusBaru === 'Dibatalkan') {
                $pesanan->pembayaran?->update(['status_pembayaran' => 'dibatalkan']);
            }

            if ($pesanan->meja) {
                if (in_array($statusBaru, ['Diterima', 'Diproses'], true)) {
                    $pesanan->meja->update(['status' => 'terisi']);
                } elseif (in_array($statusBaru, ['Selesai', 'Dibatalkan'], true)) {
                    $masihAktif = Pesanan::query()
                        ->where('meja_id', $pesanan->meja_id)
                        ->whereIn('status_pesanan', ['Pesanan Masuk', 'Diterima', 'Diproses'])
                        ->exists();

                    if (! $masihAktif) {
                        $pesanan->meja->update(['status' => 'tersedia']);
                    }
                }
            }

            return true;
        });
    }
}
