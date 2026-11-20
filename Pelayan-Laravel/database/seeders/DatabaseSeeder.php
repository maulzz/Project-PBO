<?php

namespace Database\Seeders;

use App\Models\Condiment;
use App\Models\DetailPesanan;
use App\Models\KategoriMenu;
use App\Models\Meja;
use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Akun Admin / Pelayan
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin Pelayan',
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => Hash::make('admin12345678'),
                'role' => 'pelayan',
                'email_verified_at' => now(),
            ]
        );

        // Akun Pemilik
        User::updateOrCreate(
            ['email' => 'pemilik@gmail.com'],
            [
                'name' => 'Pemilik Cafe',
                'username' => 'pemilik',
                'email' => 'pemilik@gmail.com',
                'password' => Hash::make('pemilik12345678'),
                'role' => 'pemilik',
                'email_verified_at' => now(),
            ]
        );

        // Meja
        for ($i = 1; $i <= 10; $i++) {
            Meja::updateOrCreate(
                ['nomor_meja' => 'M'.str_pad($i, 2, '0', STR_PAD_LEFT)],
                [
                    'qr_code' => 'QR-MEJA-'.str_pad($i, 2, '0', STR_PAD_LEFT),
                    'status' => 'tersedia',
                    'kapasitas' => $i <= 5 ? 2 : 4,
                ]
            );
        }

        // Kategori Menu
        $kategoriMinuman = KategoriMenu::updateOrCreate(['nama_kategori' => 'Minuman'], ['deskripsi' => 'Berbagai pilihan minuman']);
        $kategoriMakanan = KategoriMenu::updateOrCreate(['nama_kategori' => 'Makanan'], ['deskripsi' => 'Berbagai pilihan makanan']);
        $kategoriSnack = KategoriMenu::updateOrCreate(['nama_kategori' => 'Snack'], ['deskripsi' => 'Camilan dan makanan ringan']);

        // Condiment
        $extraShot = Condiment::updateOrCreate(['nama_condiment' => 'Extra Shot'], ['harga_condiment' => 5000, 'status_ketersediaan' => true]);
        $gulaMerah = Condiment::updateOrCreate(['nama_condiment' => 'Gula Aren'], ['harga_condiment' => 3000, 'status_ketersediaan' => true]);
        $keju = Condiment::updateOrCreate(['nama_condiment' => 'Topping Keju'], ['harga_condiment' => 5000, 'status_ketersediaan' => true]);
        $pedasLevel = Condiment::updateOrCreate(['nama_condiment' => 'Extra Pedas'], ['harga_condiment' => 2000, 'status_ketersediaan' => true]);
        $whipCream = Condiment::updateOrCreate(['nama_condiment' => 'Whip Cream'], ['harga_condiment' => 4000, 'status_ketersediaan' => true]);

        // Menu Minuman
        $esKopi = Menu::updateOrCreate(['nama_menu' => 'Es Kopi Susu'], [
            'kategori_menu_id' => $kategoriMinuman->id,
            'deskripsi' => 'Kopi susu segar dengan es batu',
            'harga' => 18000,
            'stok' => 50,
            'is_aktif' => true,
        ]);
        $esKopi->condiments()->sync([$extraShot->id, $gulaMerah->id, $whipCream->id]);

        $matchaLatte = Menu::updateOrCreate(['nama_menu' => 'Matcha Latte'], [
            'kategori_menu_id' => $kategoriMinuman->id,
            'deskripsi' => 'Matcha premium dengan susu segar',
            'harga' => 22000,
            'stok' => 30,
            'is_aktif' => true,
        ]);
        $matchaLatte->condiments()->sync([$whipCream->id, $gulaMerah->id]);

        $esTeh = Menu::updateOrCreate(['nama_menu' => 'Es Teh Manis'], [
            'kategori_menu_id' => $kategoriMinuman->id,
            'deskripsi' => 'Teh manis segar',
            'harga' => 8000,
            'stok' => 100,
            'is_aktif' => true,
        ]);

        $jeriuk = Menu::updateOrCreate(['nama_menu' => 'Es Jeruk Peras'], [
            'kategori_menu_id' => $kategoriMinuman->id,
            'deskripsi' => 'Jeruk segar diperas langsung',
            'harga' => 12000,
            'stok' => 40,
            'is_aktif' => true,
        ]);

        // Menu Makanan
        $nasiGoreng = Menu::updateOrCreate(['nama_menu' => 'Nasi Goreng Spesial'], [
            'kategori_menu_id' => $kategoriMakanan->id,
            'deskripsi' => 'Nasi goreng dengan telur, ayam, dan sayuran',
            'harga' => 25000,
            'stok' => 30,
            'is_aktif' => true,
        ]);
        $nasiGoreng->condiments()->sync([$pedasLevel->id, $keju->id]);

        $mieGoreng = Menu::updateOrCreate(['nama_menu' => 'Mie Goreng'], [
            'kategori_menu_id' => $kategoriMakanan->id,
            'deskripsi' => 'Mie goreng dengan bumbu khas',
            'harga' => 22000,
            'stok' => 25,
            'is_aktif' => true,
        ]);
        $mieGoreng->condiments()->sync([$pedasLevel->id]);

        $rotiBakar = Menu::updateOrCreate(['nama_menu' => 'Roti Bakar Coklat Keju'], [
            'kategori_menu_id' => $kategoriMakanan->id,
            'deskripsi' => 'Roti bakar lembut dengan coklat dan keju',
            'harga' => 15000,
            'stok' => 20,
            'is_aktif' => true,
        ]);
        $rotiBakar->condiments()->sync([$keju->id]);

        // Menu Snack
        Menu::updateOrCreate(['nama_menu' => 'Kentang Goreng'], [
            'kategori_menu_id' => $kategoriSnack->id,
            'deskripsi' => 'Kentang goreng renyah',
            'harga' => 15000,
            'stok' => 40,
            'is_aktif' => true,
        ]);

        Menu::updateOrCreate(['nama_menu' => 'Pisang Goreng Crispy'], [
            'kategori_menu_id' => $kategoriSnack->id,
            'deskripsi' => 'Pisang goreng dengan balutan tepung crispy',
            'harga' => 12000,
            'stok' => 35,
            'is_aktif' => true,
        ]);

        // Sample Pesanan
        $meja1 = Meja::where('nomor_meja', 'M01')->first();
        $meja2 = Meja::where('nomor_meja', 'M02')->first();
        $meja3 = Meja::where('nomor_meja', 'M03')->first();
        $meja5 = Meja::where('nomor_meja', 'M05')->first();
        $admin = User::where('email', 'admin@gmail.com')->first();

        // 1. Pesanan Baru Masuk
        $pesanan1 = Pesanan::updateOrCreate(
            ['nomor_pesanan' => 'ORD-20261120-A001'],
            [
                'meja_id' => $meja1->id,
                'nama_pengunjung' => 'Budi Santoso',
                'tanggal_pesanan' => now()->subMinutes(10),
                'status_pesanan' => 'Pesanan Masuk',
                'total_harga' => 50000,
                'catatan' => 'Jangan terlalu manis untuk kopinya',
            ]
        );
        $detail1 = DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan1->id, 'menu_id' => $esKopi->id],
            [
                'harga_satuan' => 18000,
                'jumlah' => 1,
                'catatan' => 'Sedikit es',
                'subtotal' => 23000,
            ]
        );
        $detail1->condiments()->sync([$extraShot->id]);

        $detail2 = DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan1->id, 'menu_id' => $nasiGoreng->id],
            [
                'harga_satuan' => 25000,
                'jumlah' => 1,
                'catatan' => 'Pedas sedang',
                'subtotal' => 27000,
            ]
        );
        $detail2->condiments()->sync([$pedasLevel->id]);

        Pembayaran::updateOrCreate(
            ['pesanan_id' => $pesanan1->id],
            [
                'metode_pembayaran' => 'kasir',
                'total_bayar' => 50000,
                'status_pembayaran' => 'menunggu',
                'transaksi_ref' => 'CASH-001',
            ]
        );

        // 2. Pesanan Diterima
        $pesanan2 = Pesanan::updateOrCreate(
            ['nomor_pesanan' => 'ORD-20261120-B002'],
            [
                'meja_id' => $meja3->id,
                'nama_pengunjung' => 'Siti Rahma',
                'tanggal_pesanan' => now()->subMinutes(25),
                'status_pesanan' => 'Diterima',
                'total_harga' => 38000,
                'pelayan_id' => $admin->id,
                'catatan' => 'Pisang goreng minta ekstra garing',
            ]
        );
        $detail3 = DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan2->id, 'menu_id' => $matchaLatte->id],
            [
                'harga_satuan' => 22000,
                'jumlah' => 1,
                'catatan' => '',
                'subtotal' => 26000,
            ]
        );
        $detail3->condiments()->sync([$whipCream->id]);

        DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan2->id, 'menu_id' => Menu::where('nama_menu', 'Pisang Goreng Crispy')->first()->id],
            [
                'harga_satuan' => 12000,
                'jumlah' => 1,
                'catatan' => 'Ekstra garing',
                'subtotal' => 12000,
            ]
        );

        Pembayaran::updateOrCreate(
            ['pesanan_id' => $pesanan2->id],
            [
                'metode_pembayaran' => 'online',
                'total_bayar' => 38000,
                'status_pembayaran' => 'lunas',
                'transaksi_ref' => 'TRX-ONLINE-98214',
                'waktu_bayar' => now()->subMinutes(24),
            ]
        );

        // 3. Pesanan Diproses di Dapur/Bar
        $pesanan3 = Pesanan::updateOrCreate(
            ['nomor_pesanan' => 'ORD-20261120-C003'],
            [
                'meja_id' => $meja5->id,
                'nama_pengunjung' => 'Dimas Anggara',
                'tanggal_pesanan' => now()->subMinutes(40),
                'status_pesanan' => 'Diproses',
                'total_harga' => 32000,
                'pelayan_id' => $admin->id,
                'catatan' => 'Mie goreng tidak pakai bawang goreng',
            ]
        );
        DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan3->id, 'menu_id' => $mieGoreng->id],
            [
                'harga_satuan' => 22000,
                'jumlah' => 1,
                'catatan' => 'Tanpa bawang goreng',
                'subtotal' => 24000,
            ]
        );
        DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan3->id, 'menu_id' => $esTeh->id],
            [
                'harga_satuan' => 8000,
                'jumlah' => 1,
                'catatan' => '',
                'subtotal' => 8000,
            ]
        );
        Pembayaran::updateOrCreate(
            ['pesanan_id' => $pesanan3->id],
            [
                'metode_pembayaran' => 'kasir',
                'total_bayar' => 32000,
                'status_pembayaran' => 'menunggu',
                'transaksi_ref' => 'CASH-003',
            ]
        );

        // 4. Pesanan Selesai
        $pesanan4 = Pesanan::updateOrCreate(
            ['nomor_pesanan' => 'ORD-20261120-D004'],
            [
                'meja_id' => $meja2->id,
                'nama_pengunjung' => 'Anisa Putri',
                'tanggal_pesanan' => now()->subHours(1),
                'status_pesanan' => 'Selesai',
                'total_harga' => 32000,
                'pelayan_id' => $admin->id,
                'catatan' => '',
            ]
        );
        DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan4->id, 'menu_id' => $rotiBakar->id],
            [
                'harga_satuan' => 15000,
                'jumlah' => 1,
                'catatan' => '',
                'subtotal' => 20000,
            ]
        );
        DetailPesanan::updateOrCreate(
            ['pesanan_id' => $pesanan4->id, 'menu_id' => $jeriuk->id],
            [
                'harga_satuan' => 12000,
                'jumlah' => 1,
                'catatan' => '',
                'subtotal' => 12000,
            ]
        );
        Pembayaran::updateOrCreate(
            ['pesanan_id' => $pesanan4->id],
            [
                'metode_pembayaran' => 'online',
                'total_bayar' => 32000,
                'status_pembayaran' => 'lunas',
                'transaksi_ref' => 'TRX-ONLINE-98100',
                'waktu_bayar' => now()->subHours(1),
            ]
        );
    }
}
