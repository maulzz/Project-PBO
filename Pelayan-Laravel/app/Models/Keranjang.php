<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['meja_id', 'session_id', 'nama_pengunjung', 'total_harga', 'tanggal_dibuat'])]
class Keranjang extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_harga' => 'decimal:2',
            'tanggal_dibuat' => 'datetime',
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
     * @return HasMany<KeranjangItem, $this>
     */
    public function keranjangItems(): HasMany
    {
        return $this->hasMany(KeranjangItem::class);
    }
}
