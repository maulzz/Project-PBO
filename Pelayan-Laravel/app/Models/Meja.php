<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nomor_meja', 'qr_code', 'status', 'kapasitas'])]
class Meja extends Model
{
    /**
     * @return HasMany<Pesanan, $this>
     */
    public function pesanans(): HasMany
    {
        return $this->hasMany(Pesanan::class);
    }

    /**
     * @return HasMany<Keranjang, $this>
     */
    public function keranjangs(): HasMany
    {
        return $this->hasMany(Keranjang::class);
    }

    public function isTersedia(): bool
    {
        return $this->status === 'tersedia';
    }
}
