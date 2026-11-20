<?php

namespace App\Http\Resources\Api;

use App\Models\Meja;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Meja
 */
class MejaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_meja' => $this->nomor_meja,
            'qr_code' => $this->qr_code,
            'status' => $this->status,
            'kapasitas' => $this->kapasitas,
        ];
    }
}
