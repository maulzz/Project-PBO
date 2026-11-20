<?php

namespace App\Http\Resources\Api;

use App\Models\Condiment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Condiment
 */
class CondimentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_condiment' => $this->nama_condiment,
            'harga_condiment' => (float) $this->harga_condiment,
            'tersedia' => (bool) $this->status_ketersediaan,
        ];
    }
}
