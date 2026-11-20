<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'nomor_pesanan',
    'meja_id',
    'nama_pengunjung',
    'tanggal_pesanan',
    'status_pesanan',
    'total_harga',
    'pelayan_id',
    'dibatalkan_oleh',
    'waktu_dibatalkan',
    'catatan',
])]
class Pesanan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_harga' => 'decimal:2',
            'tanggal_pesanan' => 'datetime',
            'waktu_dibatalkan' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Meja, $this>
     */
    public function meja(): BelongsTo
    {
        return $this->belongsTo(Meja::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pelayan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pelayan_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembatal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    /**
     * @return HasMany<DetailPesanan, $this>
     */
    public function detailPesanans(): HasMany
    {
        return $this->hasMany(DetailPesanan::class);
    }

    /**
     * @return HasOne<Pembayaran, $this>
     */
    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class);
    }

    public function hitungTotalHarga(): float
    {
        return $this->detailPesanans->sum('subtotal');
    }

    public function isDapatDibatalkan(): bool
    {
        return in_array($this->status_pesanan, ['Pesanan Masuk', 'Diterima']);
    }
}
