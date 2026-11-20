<?php

namespace Tests\Feature;

use App\Filament\Pemilik\Resources\MonitoringStokResource;
use App\Filament\Pemilik\Resources\RiwayatPembayaranResource;
use App\Filament\Pemilik\Resources\RiwayatPesananResource;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class PemilikFilamentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $pemilik;

    protected User $pelayan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pemilik = User::factory()->create(['role' => 'pemilik', 'name' => 'Pemilik Uji']);
        $this->pelayan = User::factory()->create(['role' => 'pelayan', 'name' => 'Pelayan Uji']);
    }

    public function test_pemilik_dapat_mengakses_semua_halaman_panel_pemilik(): void
    {
        $pesanan = $this->buatPesanan();
        $this->actingAs($this->pemilik);

        foreach ([
            '/pemilik',
            '/pemilik/riwayat-pesanans',
            "/pemilik/riwayat-pesanans/{$pesanan->id}",
            '/pemilik/riwayat-pembayarans',
            '/pemilik/monitoring-stok',
        ] as $url) {
            $this->get($url)->assertSuccessful();
        }
    }

    public function test_pelayan_tidak_dapat_mengakses_panel_pemilik(): void
    {
        $this->actingAs($this->pelayan);

        $this->get('/pemilik')->assertForbidden();
    }

    public function test_pelaku_aksi_konfirmasi_pembayaran_dan_pembatalan_tercatat_untuk_pemilik(): void
    {
        $pesananBayar = $this->buatPesanan();
        $pesananBatal = $this->buatPesanan();

        // Pelayan melakukan konfirmasi pembayaran kasir dan pembatalan
        $this->actingAs($this->pelayan);

        Livewire::test(ListPesanans::class)
            ->callTableAction('konfirmasiBayar', $pesananBayar, data: [
                'metode_pembayaran' => 'kasir',
                'uang_diterima' => 50000,
            ])
            ->assertHasNoTableActionErrors();

        Livewire::test(ListPesanans::class)
            ->callTableAction('batalkan', $pesananBatal);

        $this->assertSame($this->pelayan->id, $pesananBayar->refresh()->pembayaran->dikonfirmasi_oleh);
        $this->assertSame($this->pelayan->id, $pesananBatal->refresh()->dibatalkan_oleh);
        $this->assertNotNull($pesananBatal->waktu_dibatalkan);

        // Pemilik melihat pelaku aksi di dashboard pemilik
        $this->actingAs($this->pemilik);

        $this->get('/pemilik/riwayat-pembayarans')
            ->assertSuccessful()
            ->assertSee($this->pelayan->name);

        $this->get("/pemilik/riwayat-pesanans/{$pesananBatal->id}")
            ->assertSuccessful()
            ->assertSee($this->pelayan->name);
    }

    public function test_pemilik_hanya_memiliki_akses_read_only(): void
    {
        $pesanan = $this->buatPesanan();
        $menu = Menu::first();
        $pembayaran = $pesanan->pembayaran()->create([
            'metode_pembayaran' => 'kasir',
            'total_bayar' => $pesanan->total_harga,
            'status_pembayaran' => 'menunggu',
        ]);

        $this->assertFalse(RiwayatPesananResource::canCreate());
        $this->assertFalse(RiwayatPesananResource::canEdit($pesanan));
        $this->assertFalse(RiwayatPesananResource::canDelete($pesanan));

        $this->assertFalse(RiwayatPembayaranResource::canCreate());
        $this->assertFalse(RiwayatPembayaranResource::canEdit($pembayaran));
        $this->assertFalse(RiwayatPembayaranResource::canDelete($pembayaran));

        $this->assertFalse(MonitoringStokResource::canCreate());
        $this->assertFalse(MonitoringStokResource::canEdit($menu));
        $this->assertFalse(MonitoringStokResource::canDelete($menu));
    }

    protected function buatPesanan(): Pesanan
    {
        $kategori = KategoriMenu::create(['nama_kategori' => 'Kategori Pemilik Test']);
        $menu = Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'Menu Pemilik Test '.uniqid(),
            'harga' => 15000,
            'stok' => 10,
            'is_aktif' => true,
        ]);
        $meja = Meja::create(['nomor_meja' => 'P-'.uniqid(), 'qr_code' => 'QR-'.uniqid()]);

        $pesanan = Pesanan::create([
            'nomor_pesanan' => 'PMK-'.uniqid(),
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Tamu Pemilik',
            'status_pesanan' => 'Pesanan Masuk',
            'total_harga' => 15000,
        ]);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'harga_satuan' => 15000,
            'jumlah' => 1,
            'subtotal' => 15000,
        ]);

        return $pesanan;
    }
}
