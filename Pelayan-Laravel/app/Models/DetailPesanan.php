<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['pesanan_id', 'menu_id', 'harga_satuan', 'jumlah', 'catatan', 'subtotal'])]
class DetailPesanan extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
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
     * @return BelongsTo<Menu, $this>
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * @return BelongsToMany<Condiment, $this>
     */
    public function condiments(): BelongsToMany
    {
        return $this->belongsToMany(Condiment::class, 'detail_pesanan_condiment')
            ->withPivot('harga_condiment')
            ->withTimestamps();
    }

    public function hitungSubtotal(): float
    {
        $totalCondiment = $this->condiments->sum('pivot.harga_condiment');

        return ($this->harga_satuan + $totalCondiment) * $this->jumlah;
    }
}
