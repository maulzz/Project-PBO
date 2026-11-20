<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Services\Pembayaran\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransCallbackController extends Controller
{
    public function __invoke(Request $request, MidtransService $midtrans): JsonResponse
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');

        if (! $midtrans->verifikasiSignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            return response()->json(['message' => 'Signature tidak valid.'], 403);
        }

        $pesanan = Pesanan::with('pembayaran')->where('nomor_pesanan', $orderId)->first();

        if (! $pesanan) {
            return response()->json(['message' => 'Pesanan tidak ditemukan.'], 404);
        }

        $statusBaru = $midtrans->sinkronkanStatusPembayaran(
            $pesanan,
            (string) $request->input('transaction_status'),
            $request->input('fraud_status'),
            $request->input('transaction_id'),
        );

        return response()->json([
            'message' => 'Notifikasi Midtrans diproses.',
            'status_pembayaran' => $statusBaru,
        ]);
    }
}
