<?php

namespace App\Http\Resources\Api;

use App\Models\KategoriMenu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KategoriMenu
 */
class KategoriMenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_kategori' => $this->nama_kategori,
            'deskripsi' => $this->deskripsi,
            'menus' => MenuResource::collection($this->whenLoaded('menus')),
        ];
    }
}
