<?php

namespace App\Http\Resources\Api;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * @mixin Menu
 */
class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $stokHabis = $this->stok <= 0 || ! $this->is_aktif;

        return [
            'id' => $this->id,
            'nama_menu' => $this->nama_menu,
            'deskripsi' => $this->deskripsi,
            'harga' => (float) $this->harga,
            'gambar_url' => $this->gambar ? Storage::disk('public')->url($this->gambar) : null,
            'stok_habis' => $stokHabis,
            'bisa_dipesan' => ! $stokHabis,
            'status_teks' => $stokHabis ? 'Stok Habis' : 'Tersedia',
            'kategori' => $this->whenLoaded('kategoriMenu', fn (): array => [
                'id' => $this->kategoriMenu->id,
                'nama_kategori' => $this->kategoriMenu->nama_kategori,
            ]),
            'condiments' => CondimentResource::collection($this->whenLoaded('condiments')),
        ];
    }
}
