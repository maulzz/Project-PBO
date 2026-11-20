<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kategori_menu_id', 'nama_menu', 'deskripsi', 'harga', 'stok', 'gambar', 'is_aktif'])]
class Menu extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<KategoriMenu, $this>
     */
    public function kategoriMenu(): BelongsTo
    {
        return $this->belongsTo(KategoriMenu::class);
    }

    /**
     * @return BelongsToMany<Condiment, $this>
     */
    public function condiments(): BelongsToMany
    {
        return $this->belongsToMany(Condiment::class, 'menu_condiment');
    }

    /**
     * @return HasMany<DetailPesanan, $this>
     */
    public function detailPesanans(): HasMany
    {
        return $this->hasMany(DetailPesanan::class);
    }

    public function isStokTersedia(): bool
    {
        return $this->stok > 0 && $this->is_aktif;
    }

    public function kurangiStok(int $jumlah): void
    {
        $this->decrement('stok', $jumlah);
    }
}
