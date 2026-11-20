<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['keranjang_id', 'menu_id', 'harga_satuan', 'jumlah', 'catatan', 'subtotal'])]
class KeranjangItem extends Model
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
     * @return BelongsTo<Keranjang, $this>
     */
    public function keranjang(): BelongsTo
    {
        return $this->belongsTo(Keranjang::class);
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
        return $this->belongsToMany(Condiment::class, 'keranjang_item_condiment')
            ->withPivot('harga_condiment')
            ->withTimestamps();
    }
}
