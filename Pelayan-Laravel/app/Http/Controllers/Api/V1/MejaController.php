<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MejaResource;
use App\Models\Meja;
use Illuminate\Http\JsonResponse;

class MejaController extends Controller
{
    public function showByQrCode(string $qrCode): MejaResource|JsonResponse
    {
        $meja = Meja::where('qr_code', $qrCode)->first();

        if (! $meja) {
            return response()->json([
                'message' => 'QR Code meja tidak ditemukan.',
            ], 404);
        }

        return new MejaResource($meja);
    }
}
