<?php

namespace App\Services\Pembayaran;

use App\Contracts\MetodePembayaran;
use App\Models\DetailPesanan;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    public function isProduction(): bool
    {
        return (bool) config('services.midtrans.is_production', false);
    }

    public function getClientKey(): ?string
    {
        return config('services.midtrans.client_key');
    }

    protected function getServerKey(): string
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi pada file .env.');
        }

        return $serverKey;
    }

    public function snapUrl(string $snapToken): string
    {
        $host = $this->isProduction() ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';

        return "{$host}/snap/v4/redirection/{$snapToken}";
    }

    /**
     * Membuat transaksi Snap Midtrans dan mengembalikan token + redirect_url.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function buatTransaksiSnap(Pesanan $pesanan): array
    {
        $pesanan->loadMissing('detailPesanans.menu');

        $endpoint = $this->isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $itemDetails = $pesanan->detailPesanans->map(fn (DetailPesanan $detail): array => [
            'id' => (string) $detail->menu_id,
            'price' => (int) round((float) $detail->subtotal / max(1, $detail->jumlah)),
            'quantity' => $detail->jumlah,
            'name' => mb_substr($detail->menu?->nama_menu ?? 'Menu', 0, 50),
        ])->values()->all();

        $grossAmount = array_sum(array_map(
            fn (array $item): int => $item['price'] * $item['quantity'],
            $itemDetails,
        ));

        $payload = [
            'transaction_details' => [
                'order_id' => $pesanan->nomor_pesanan,
                'gross_amount' => $grossAmount > 0 ? $grossAmount : (int) round((float) $pesanan->total_harga),
            ],
            'customer_details' => [
                'first_name' => $pesanan->nama_pengunjung,
            ],
            'item_details' => $itemDetails,
        ];

        $response = Http::withBasicAuth($this->getServerKey(), '')
            ->acceptJson()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membuat transaksi Midtrans: '.$response->body());
        }

        /** @var array{token: string, redirect_url: string} $data */
        $data = $response->json();

        return $data;
    }

    /**
     * Mengambil status transaksi langsung dari API Midtrans (berguna untuk demo localhost tanpa ngrok).
     *
     * @return array<string, mixed>
     */
    public function cekStatusTransaksi(string $nomorPesanan): array
    {
        $baseUrl = $this->isProduction()
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';

        $response = Http::withBasicAuth($this->getServerKey(), '')
            ->acceptJson()
            ->get("{$baseUrl}/{$nomorPesanan}/status");

        if ($response->failed()) {
            throw new RuntimeException('Gagal mengecek status transaksi Midtrans: '.$response->body());
        }

        return $response->json();
    }

    public function verifikasiSignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->getServerKey());

        return hash_equals($expected, $signatureKey);
    }

    /**
     * Menyelaraskan status pembayaran lokal berdasarkan status transaksi Midtrans.
     */
    public function sinkronkanStatusPembayaran(
        Pesanan $pesanan,
        string $transactionStatus,
        ?string $fraudStatus = null,
        ?string $transactionId = null,
    ): string {
        if (
            $transactionStatus === 'settlement'
            || ($transactionStatus === 'capture' && ($fraudStatus === null || $fraudStatus === 'accept'))
        ) {
            /** @var MetodePembayaran $metode */
            $metode = new PembayaranOnline($pesanan, $transactionId);
            $metode->prosesPembayaran((float) $pesanan->total_harga);

            return 'lunas';
        }

        if ($transactionStatus === 'pending') {
            $pesanan->pembayaran?->update(['status_pembayaran' => 'menunggu']);

            return 'menunggu';
        }

        $statusLokal = $transactionStatus === 'cancel' ? 'dibatalkan' : 'gagal';

        $pesanan->pembayaran?->update([
            'status_pembayaran' => $statusLokal,
            'transaksi_ref' => $transactionId ?: $pesanan->pembayaran?->transaksi_ref,
        ]);

        return $statusLokal;
    }
}
