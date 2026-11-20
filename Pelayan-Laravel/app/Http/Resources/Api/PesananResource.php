<?php

namespace App\Http\Resources\Api;

use App\Models\DetailPesanan;
use App\Models\Pesanan;
use App\Services\Pembayaran\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pesanan
 */
class PesananResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nomor_pesanan' => $this->nomor_pesanan,
            'nomor_meja' => $this->meja?->nomor_meja,
            'nama_pengunjung' => $this->nama_pengunjung,
            'status_pesanan' => $this->status_pesanan,
            'total_harga' => (float) $this->total_harga,
            'tanggal_pesanan' => $this->tanggal_pesanan?->toIso8601String(),
            'catatan' => $this->catatan,
            'pembayaran' => $this->whenLoaded('pembayaran', function (): ?array {
                if (! $this->pembayaran) {
                    return null;
                }

                $midtrans = app(MidtransService::class);
                $isSnapPending = $this->pembayaran->metode_pembayaran === 'online'
                    && $this->pembayaran->status_pembayaran === 'menunggu'
                    && filled($this->pembayaran->transaksi_ref);

                return [
                    'metode_pembayaran' => $this->pembayaran->metode_pembayaran,
                    'total_bayar' => (float) $this->pembayaran->total_bayar,
                    'status_pembayaran' => $this->pembayaran->status_pembayaran,
                    'transaksi_ref' => $this->pembayaran->transaksi_ref,
                    'waktu_bayar' => $this->pembayaran->waktu_bayar?->toIso8601String(),
                    'snap_token' => $isSnapPending ? $this->pembayaran->transaksi_ref : null,
                    'snap_url' => $isSnapPending ? $midtrans->snapUrl($this->pembayaran->transaksi_ref) : null,
                    'client_key' => $isSnapPending ? $midtrans->getClientKey() : null,
                ];
            }),
            'items' => $this->whenLoaded('detailPesanans', fn () => $this->detailPesanans->map(
                fn (DetailPesanan $detail): array => [
                    'id' => $detail->id,
                    'menu_id' => $detail->menu_id,
                    'nama_menu' => $detail->menu?->nama_menu,
                    'harga_satuan' => (float) $detail->harga_satuan,
                    'jumlah' => $detail->jumlah,
                    'catatan' => $detail->catatan,
                    'subtotal' => (float) $detail->subtotal,
                    'condiments' => $detail->condiments->map(fn ($condiment): array => [
                        'id' => $condiment->id,
                        'nama_condiment' => $condiment->nama_condiment,
                        'harga_condiment' => (float) $condiment->pivot->harga_condiment,
                    ])->values(),
                ],
            )->values()),
        ];
    }
}
