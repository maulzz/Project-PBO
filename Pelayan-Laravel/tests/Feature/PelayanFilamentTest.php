<?php

namespace Tests\Feature;

use App\Contracts\MetodePembayaran;
use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Condiments\Pages\CreateCondiment;
use App\Filament\Resources\Mejas\Pages\ListMejas;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Filament\Resources\Pesanans\Pages\EditPesanan;
use App\Filament\Resources\Pesanans\Pages\ListPesanans;
use App\Filament\Resources\Pesanans\Pages\ViewPesanan;
use App\Filament\Resources\Pesanans\RelationManagers\DetailPesanansRelationManager;
use App\Filament\Widgets\PesananNotifier;
use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pelayan;
use App\Models\Pemilik;
use App\Models\Pengguna;
use App\Models\Pesanan;
use App\Models\User;
use App\Services\Pembayaran\PembayaranKasir;
use App\Services\Pembayaran\PembayaranOnline;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PelayanFilamentTest extends TestCase
{
    use DatabaseTransactions;

    protected User $pelayan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pelayan = User::factory()->create(['role' => 'pelayan']);
        $this->actingAs($this->pelayan);
    }

    public function test_halaman_login_dapat_diakses_tamu(): void
    {
        auth()->logout();

        $this->get('/admin/login')->assertSuccessful();
    }

    public function test_semua_halaman_daftar_tambah_dan_dashboard_dapat_dibuka(): void
    {
        foreach (['', '/pesanans', '/menus', '/menus/create', '/kategori-menus/create', '/condiments/create', '/mejas/create', '/pesanans/create'] as $path) {
            $this->get('/admin'.$path)->assertSuccessful();
        }
    }

    public function test_halaman_edit_menu_dapat_dibuka(): void
    {
        $menu = $this->buatMenu();

        $this->get("/admin/menus/{$menu->id}/edit")->assertSuccessful();
    }

    public function test_pelayan_dapat_menambah_condiment(): void
    {
        Livewire::test(CreateCondiment::class)
            ->fillForm([
                'nama_condiment' => 'Saus Test',
                'harga_condiment' => 2500,
                'status_ketersediaan' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('condiments', ['nama_condiment' => 'Saus Test']);
    }

    public function test_pelayan_dapat_mengubah_stok_menu(): void
    {
        $menu = $this->buatMenu();

        Livewire::test(EditMenu::class, ['record' => $menu->getRouteKey()])
            ->fillForm(['stok' => 99])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(99, $menu->refresh()->stok);
    }

    public function test_pelayan_dapat_menghapus_menu(): void
    {
        $menu = $this->buatMenu();

        Livewire::test(ListMenus::class)->callTableAction('delete', $menu);

        $this->assertModelMissing($menu);
    }

    public function test_menerima_pesanan_mengurangi_stok(): void
    {
        [$pesanan, $menu] = $this->buatPesanan(jumlah: 3, stok: 10);

        Livewire::test(ListPesanans::class)->callTableAction('terima', $pesanan);

        $this->assertSame('Diterima', $pesanan->refresh()->status_pesanan);
        $this->assertSame(7, $menu->refresh()->stok);
    }

    public function test_pesanan_tidak_bisa_diterima_jika_stok_kurang(): void
    {
        [$pesanan, $menu] = $this->buatPesanan(jumlah: 5, stok: 2);

        Livewire::test(ListPesanans::class)->callTableAction('terima', $pesanan);

        $this->assertSame('Pesanan Masuk', $pesanan->refresh()->status_pesanan);
        $this->assertSame(2, $menu->refresh()->stok);
    }

    public function test_pelayan_dapat_konfirmasi_pembayaran_tunai_di_kasir(): void
    {
        [$pesanan] = $this->buatPesanan();
        $pesanan->pembayaran()->create([
            'metode_pembayaran' => 'kasir',
            'total_bayar' => $pesanan->total_harga,
            'status_pembayaran' => 'menunggu',
        ]);

        Livewire::test(ListPesanans::class)
            ->callTableAction('konfirmasiBayar', $pesanan, data: [
                'metode_pembayaran' => 'kasir',
                'uang_diterima' => 100000,
            ])
            ->assertHasNoTableActionErrors();

        $pembayaran = $pesanan->refresh()->pembayaran;
        $this->assertSame('lunas', $pembayaran->status_pembayaran);
        $this->assertNotNull($pembayaran->waktu_bayar);
    }

    public function test_pembayaran_tunai_ditolak_jika_uang_kurang(): void
    {
        [$pesanan] = $this->buatPesanan();

        Livewire::test(ListPesanans::class)
            ->callTableAction('konfirmasiBayar', $pesanan, data: [
                'metode_pembayaran' => 'kasir',
                'uang_diterima' => 1000,
            ])
            ->assertHasTableActionErrors(['uang_diterima']);

        $this->assertNull($pesanan->refresh()->pembayaran);
    }

    public function test_pesanan_selesai_tidak_dapat_dihapus(): void
    {
        [$pesanan] = $this->buatPesanan();
        $pesanan->update(['status_pesanan' => 'Selesai']);

        Livewire::test(ListPesanans::class)->assertTableActionDoesNotExist('delete');
    }

    public function test_notifier_mengirim_event_saat_ada_pesanan_baru(): void
    {
        $komponen = Livewire::test(PesananNotifier::class);

        $this->buatPesanan();

        $komponen->call('cekPesananBaru')->assertDispatched('pesanan-baru');
    }

    public function test_notifier_tidak_mengirim_event_tanpa_pesanan_baru(): void
    {
        $this->buatPesanan();

        Livewire::test(PesananNotifier::class)
            ->call('cekPesananBaru')
            ->assertNotDispatched('pesanan-baru');
    }

    public function test_halaman_detail_pesanan_terpisah_dari_halaman_edit(): void
    {
        [$pesanan, $menu] = $this->buatPesanan();

        $this->get("/admin/pesanans/{$pesanan->id}")
            ->assertSuccessful()
            ->assertSee($pesanan->nomor_pesanan);

        $this->get("/admin/pesanans/{$pesanan->id}/edit")->assertSuccessful();

        $this->assertTrue(DetailPesanansRelationManager::canViewForRecord($pesanan, ViewPesanan::class));
        $this->assertFalse(DetailPesanansRelationManager::canViewForRecord($pesanan, EditPesanan::class));
    }

    public function test_meja_memiliki_aksi_qr_code_dan_modal_dapat_dirender(): void
    {
        $meja = Meja::create([
            'nomor_meja' => 'QR-TEST-01',
            'qr_code' => 'QR-CODE-TEST-01',
            'status' => 'tersedia',
        ]);

        Livewire::test(ListMejas::class)
            ->assertTableActionVisible('qrCode', $meja);

        $html = view('filament.modals.meja-qr-code', ['meja' => $meja])->render();
        $this->assertStringContainsString('QR-CODE-TEST-01', $html);
        $this->assertStringContainsString('/order?meja=QR-CODE-TEST-01', $html);
        $this->assertStringContainsString('Unduh PNG', $html);
        $this->assertStringContainsString('Unduh SVG', $html);
    }

    public function test_pemilik_tidak_dapat_mengakses_panel_pelayan(): void
    {
        $pemilik = User::factory()->create(['role' => 'pemilik']);
        $this->actingAs($pemilik);

        $this->get('/admin')->assertForbidden();
    }

    public function test_pelayan_dapat_login_menggunakan_username(): void
    {
        auth()->logout();

        $username = 'pelayan_'.uniqid();
        User::factory()->create([
            'username' => $username,
            'password' => Hash::make('rahasia123'),
            'role' => 'pelayan',
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $username,
                'password' => 'rahasia123',
            ])
            ->call('authenticate')
            ->assertHasNoFormErrors();

        $this->assertAuthenticated();
    }

    public function test_pelayan_tidak_dapat_menghapus_riwayat_transaksi(): void
    {
        $this->buatPesanan();

        Livewire::test(ListPesanans::class)
            ->assertTableActionDoesNotExist('delete');
    }

    public function test_otomatisasi_status_meja_saat_terima_dan_selesai_pesanan(): void
    {
        [$pesanan] = $this->buatPesanan();
        $meja = $pesanan->meja;

        $this->assertSame('tersedia', $meja->status);

        Livewire::test(ListPesanans::class)->callTableAction('terima', $pesanan);
        $this->assertSame('terisi', $meja->refresh()->status);

        Livewire::test(ListPesanans::class)->callTableAction('proses', $pesanan);
        Livewire::test(ListPesanans::class)->callTableAction('selesai', $pesanan);
        $this->assertSame('tersedia', $meja->refresh()->status);
    }

    public function test_halaman_cetak_struk_dan_tiket_dapur_dapat_diakses(): void
    {
        [$pesanan] = $this->buatPesanan();

        $this->get(route('pesanan.print.struk', $pesanan))
            ->assertSuccessful()
            ->assertSee($pesanan->nomor_pesanan);

        $this->get(route('pesanan.print.tiket-dapur', $pesanan))
            ->assertSuccessful()
            ->assertSee($pesanan->nomor_pesanan);
    }

    public function test_papan_meja_dan_restock_menu_berfungsi(): void
    {
        $menu = $this->buatMenu(stok: 5);

        $this->get('/admin')->assertSuccessful()->assertSee('Papan Meja');
        $this->get('/admin/papan-meja')->assertSuccessful();

        Livewire::test(ListMenus::class)
            ->callTableAction('restock', $menu, data: ['jumlah_tambah' => 15])
            ->assertHasNoTableActionErrors();

        $this->assertSame(20, $menu->refresh()->stok);
    }

    public function test_penerapan_oop_inheritance_dan_interface_pembayaran(): void
    {
        $pelayan = Pelayan::find($this->pelayan->id);
        $this->assertInstanceOf(Pengguna::class, $pelayan);
        $this->assertNull(Pemilik::find($this->pelayan->id));

        [$pesanan] = $this->buatPesanan();
        $kasir = new PembayaranKasir($pesanan, 50000);
        $this->assertInstanceOf(MetodePembayaran::class, $kasir);
        $this->assertTrue($kasir->prosesPembayaran(10000));
        $this->assertSame('lunas', $pesanan->refresh()->pembayaran->status_pembayaran);

        [$pesanan2] = $this->buatPesanan();
        $online = new PembayaranOnline($pesanan2, 'TRX-TEST-01');
        $this->assertInstanceOf(MetodePembayaran::class, $online);
        $this->assertTrue($online->prosesPembayaran(10000));
        $this->assertSame('online', $pesanan2->refresh()->pembayaran->metode_pembayaran);
    }

    protected function buatMenu(int $stok = 10): Menu
    {
        $kategori = KategoriMenu::create(['nama_kategori' => 'Kategori Test']);

        return Menu::create([
            'kategori_menu_id' => $kategori->id,
            'nama_menu' => 'Menu Test '.uniqid(),
            'harga' => 10000,
            'stok' => $stok,
            'is_aktif' => true,
        ]);
    }

    /**
     * @return array{0: Pesanan, 1: Menu}
     */
    protected function buatPesanan(int $jumlah = 1, int $stok = 10): array
    {
        $menu = $this->buatMenu($stok);
        $meja = Meja::create(['nomor_meja' => 'T-'.uniqid(), 'qr_code' => 'QR-'.uniqid()]);

        $pesanan = Pesanan::create([
            'nomor_pesanan' => 'TEST-'.uniqid(),
            'meja_id' => $meja->id,
            'nama_pengunjung' => 'Pengunjung Test',
            'status_pesanan' => 'Pesanan Masuk',
            'total_harga' => 10000 * $jumlah,
        ]);

        DetailPesanan::create([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'harga_satuan' => 10000,
            'jumlah' => $jumlah,
            'subtotal' => 10000 * $jumlah,
        ]);

        return [$pesanan, $menu];
    }
}
