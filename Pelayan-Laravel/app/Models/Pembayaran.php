<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'pesanan_id',
    'metode_pembayaran',
    'total_bayar',
    'status_pembayaran',
    'transaksi_ref',
    'waktu_bayar',
    'dikonfirmasi_oleh',
])]
class Pembayaran extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_bayar' => 'decimal:2',
            'waktu_bayar' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Pesanan, $this>
     */
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengonfirmasi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function isLunas(): bool
    {
        return $this->status_pembayaran === 'lunas';
    }
}
