<?php

namespace Tests\Feature;

use App\Models\Condiment;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Menu;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PengunjungApiTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pengunjung_dapat_mengenali_meja_dari_qr_code(): void
    {
        $meja = Meja::create([
            'nomor_meja' => 'API-M01',
            'qr_code' => 'QR-API-M01',
            'status' => 'tersedia',
            'kapasitas' => 4,
        ]);

        $this->getJson('/api/v1/meja/QR-API-M01')
            ->assertSuccessful()
            ->assertJsonPath('data.id', $meja->id)
            ->assertJsonPath('data.nomor_meja', 'API-M01');

        $this->getJson('/api/v1/meja/QR-TIDAK-ADA')
            ->assertNotFound();
    }

    public function test_pengunjung_dapat_melihat_kategori_dan_daftar_menu_tanpa_angka_stok(): void
    {
        $kategori = KategoriMenu::create(['nama_kategori' => 'API Kopi']);
        $condiment = Condiment::create([
            'nama_condiment' => 'API Extra Shot',
            'harga_condiment' => 5000,
            'status_ketersediaan' => true,
        ]);

        $menuTersedia = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Kopi Susu',
            'harga' => 18000,
            'stok' => 15,
            'is_aktif' => true,
        ]);
        $menuTersedia->condiments()->sync([$condiment->id]);

        $menuHabis = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Kopi Habis',
            'harga' => 20000,
            'stok' => 0,
            'is_aktif' => true,
        ]);

        $this->getJson('/api/v1/kategoris')
            ->assertSuccessful()
            ->assertJsonFragment(['nama_kategori' => 'API Kopi']);

        $response = $this->getJson("/api/v1/menus?kategori_id={$kategori->id}")
            ->assertSuccessful()
            ->assertJsonFragment([
                'nama_menu' => 'API Kopi Susu',
                'stok_habis' => false,
                'bisa_dipesan' => true,
            ])
            ->assertJsonFragment([
                'nama_menu' => 'API Kopi Habis',
                'stok_habis' => true,
                'bisa_dipesan' => false,
            ]);

        // Pastikan angka stok tidak diekspos ke pengunjung (Batasan 4.3.1)
        $this->assertArrayNotHasKey('stok', $response->json('data.0'));
    }

    public function test_pengunjung_dapat_membuat_pesanan_dan_melacak_status(): void
    {
        $meja = Meja::create(['nomor_meja' => 'API-M02', 'qr_code' => 'QR-API-M02']);
        $kategori = KategoriMenu::create(['nama_kategori' => 'API Makanan']);
        $condiment = Condiment::create([
            'nama_condiment' => 'API Keju',
            'harga_condiment' => 4000,
            'status_ketersediaan' => true,
        ]);
        $menu = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Roti Bakar',
            'harga' => 15000,
            'stok' => 10,
            'is_aktif' => true,
        ]);

        $createResponse = $this->postJson('/api/v1/pesanans', [
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Raka',
            'metode_pembayaran' => 'kasir',
            'catatan' => 'Antar cepat ya',
            'items' => [
                [
                    'menu_id' => $menu->id,
                    'jumlah' => 2,
                    'catatan' => 'Potong dua',
                    'condiment_ids' => [$condiment->id],
                ],
            ],
        ]);

        // (15000 + 4000) * 2 = 38000
        $createResponse->assertCreated()
            ->assertJsonPath('data.status_pesanan', 'Pesanan Masuk')
            ->assertJsonPath('data.total_harga', 38000)
            ->assertJsonPath('data.pembayaran.metode_pembayaran', 'kasir')
            ->assertJsonPath('data.pembayaran.status_pembayaran', 'menunggu');

        $nomorPesanan = $createResponse->json('data.nomor_pesanan');

        $this->getJson("/api/v1/pesanans/{$nomorPesanan}")
            ->assertSuccessful()
            ->assertJsonPath('data.nomor_pesanan', $nomorPesanan)
            ->assertJsonPath('data.nama_pengunjung', 'Raka');
    }

    public function test_pesanan_ditolak_jika_stok_menu_habis(): void
    {
        $meja = Meja::create(['nomor_meja' => 'API-M03', 'qr_code' => 'QR-API-M03']);
        $kategori = KategoriMenu::create(['nama_kategori' => 'API Minuman']);
        $menu = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Matcha Habis',
            'harga' => 22000,
            'stok' => 0,
            'is_aktif' => true,
        ]);

        $this->postJson('/api/v1/pesanans', [
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Sinta',
            'metode_pembayaran' => 'online',
            'items' => [
                ['menu_id' => $menu->id, 'jumlah' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.menu_id']);
    }

    public function test_checkout_online_membuat_snap_token_midtrans_dan_cek_pembayaran_menandai_lunas(): void
    {
        config([
            'services.midtrans.server_key' => 'SB-Mid-server-TEST',
            'services.midtrans.client_key' => 'SB-Mid-client-TEST',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-demo-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-demo-123',
            ], 201),
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'transaction_status' => 'settlement',
                'transaction_id' => 'MID-TRX-999',
            ], 200),
        ]);

        $meja = Meja::create(['nomor_meja' => 'API-M04', 'qr_code' => 'QR-API-M04']);
        $kategori = KategoriMenu::create(['nama_kategori' => 'API Kopi Online']);
        $menu = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Espresso',
            'harga' => 20000,
            'stok' => 10,
            'is_aktif' => true,
        ]);

        $response = $this->postJson('/api/v1/pesanans', [
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Dina',
            'metode_pembayaran' => 'online',
            'items' => [
                ['menu_id' => $menu->id, 'jumlah' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.pembayaran.snap_token', 'snap-token-demo-123')
            ->assertJsonPath('data.pembayaran.client_key', 'SB-Mid-client-TEST')
            ->assertJsonPath('data.pembayaran.status_pembayaran', 'menunggu');

        $nomorPesanan = $response->json('data.nomor_pesanan');

        $this->postJson("/api/v1/pesanans/{$nomorPesanan}/cek-pembayaran")
            ->assertSuccessful()
            ->assertJsonPath('data.pembayaran.status_pembayaran', 'lunas')
            ->assertJsonPath('data.pembayaran.transaksi_ref', 'MID-TRX-999');
    }

    public function test_midtrans_callback_memverifikasi_signature_dan_update_status_lunas(): void
    {
        config([
            'services.midtrans.server_key' => 'SB-Mid-server-TEST',
            'services.midtrans.is_production' => false,
        ]);

        Http::fake([
            'app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
                'token' => 'snap-token-cb-1',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-cb-1',
            ], 201),
        ]);

        $meja = Meja::create(['nomor_meja' => 'API-M05', 'qr_code' => 'QR-API-M05']);
        $kategori = KategoriMenu::create(['nama_kategori' => 'API Teh']);
        $menu = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'API Lemon Tea',
            'harga' => 15000,
            'stok' => 10,
            'is_aktif' => true,
        ]);

        $create = $this->postJson('/api/v1/pesanans', [
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Fajar',
            'metode_pembayaran' => 'online',
            'items' => [
                ['menu_id' => $menu->id, 'jumlah' => 1],
            ],
        ])->assertCreated();

        $orderId = $create->json('data.nomor_pesanan');
        $statusCode = '200';
        $grossAmount = '15000.00';

        // Signature salah -> 403
        $this->postJson('/api/v1/midtrans/callback', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => 'salah',
            'transaction_status' => 'settlement',
        ])->assertForbidden();

        // Signature valid -> 200 lunas
        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.'SB-Mid-server-TEST');

        $this->postJson('/api/v1/midtrans/callback', [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'transaction_id' => 'MID-CB-777',
        ])->assertSuccessful()
            ->assertJsonPath('status_pembayaran', 'lunas');
    }
}
