# Dokumentasi Sistem Pelayan Cafe (Laravel + Filament)

Dokumen ini berisi panduan lengkap mengenai arsitektur, aturan bisnis, daftar *routes*, lokasi file, serta standar pengembangan untuk sistem **Pelayan Cafe Berbasis QR Code**.

---

## 1. Ringkasan Teknis & Kredensial

- **Framework:** Laravel 13 (PHP 8.3)
- **Panel Admin/Pelayan:** Filament v5.11 + Livewire v4 (`/admin`)
- **Panel Pemilik:** Filament v5.11 (`/pemilik`) — *read-only* pemantauan stok, riwayat transaksi, pembayaran, dan audit pelaku aksi
- **Database:** PostgreSQL (Supabase — Region Singapore `ap-southeast-1`)
- **Tema Panel:** Warna utama **Orange** (`Color::Orange`), mendukung mode **Terang & Gelap**, serta **SPA Mode** (`spa(hasPrefetching: true)`).
- **Hak Akses Panel (`KNF-03`):** Panel `/admin` hanya untuk `role = 'pelayan'`, sedangkan panel `/pemilik` hanya untuk `role = 'pemilik'` (pelanggaran peran ditolak `403`). Halaman login mendukung **Email atau Username**.
- **Penerapan OOP/PBO:** Lihat rincian lengkap kelas abstrak, inheritance, dan interface pada [DOKUMENTASI_OOP.md](DOKUMENTASI_OOP.md).

### Kredensial Login Default (dari `DatabaseSeeder`)
| Peran | Email | Username | Password |
| :--- | :--- | :--- | :--- |
| **Pelayan (Utama)** | `admin@gmail.com` | `admin` | `admin12345678` |
| **Pemilik** | `pemilik@gmail.com` | `pemilik` | `pemilik12345678` |

> **URL Login Pelayan:** `/admin/login` • **Dashboard Pelayan:** `/admin`  
> **URL Login Pemilik:** `/pemilik/login` • **Dashboard Pemilik:** `/pemilik`

---

## 2. Aturan Bisnis & Alur Kerja Sistem

### A. Siklus Status Pesanan
Alur perubahan status di manajemen pesanan (`PesanansTable.php`) berjalan berurutan:
1. **`Pesanan Masuk`** *(Status awal saat pelanggan checkout)*
   - Pelayan dapat menekan tombol **Terima** atau **Batalkan**.
   - Saat **Terima** ditekan: sistem memvalidasi ketersediaan stok semua item menu. Jika stok cukup, stok menu otomatis dikurangi, `pelayan_id` dicatat dari user yang login, dan status berubah menjadi **`Diterima`**. Jika stok kurang, pesanan ditolak dan muncul notifikasi error.
2. **`Diterima`**
   - Pelayan dapat menekan **Proses** (diteruskan ke dapur/bar) atau **Batalkan** (jika dibatalkan pada tahap ini, stok menu otomatis dikembalikan).
3. **`Diproses`**
   - Pesanan sedang disiapkan. Tidak lagi bisa dibatalkan oleh pelayan. Pelayan menekan **Selesai** setelah pesanan diantar ke meja.
4. **`Selesai`**
   - Pesanan telah diantar. Status meja otomatis kembali menjadi `tersedia` jika tidak ada pesanan aktif lain di meja tersebut.
5. **`Dibatalkan`**
   - Pesanan dibatalkan dan status pembayaran ikut diubah menjadi `dibatalkan` (serta mengosongkan status meja jika tidak ada pesanan aktif lain).

> **Proteksi Riwayat Transaksi (Batasan 4.3.2):** Pelayan **tidak dapat menghapus** data pesanan/transaksi yang sudah tercatat (aksi *Delete* dan *Bulk Delete* pada tabel & edit pesanan dinonaktifkan).

### B. Aturan Pembayaran (Khusus Pelayan / Kasir)
- Pembayaran menggunakan interface `MetodePembayaran` (`PembayaranKasir` & `PembayaranOnline`).
- Pelanggan dapat memilih metode **`online`** atau **`kasir`**.
- Pada pesanan yang belum berstatus `lunas` dan belum `Dibatalkan`, tombol **Bayar** (`konfirmasiBayar`) akan muncul di baris tabel.
- Jika metode adalah **Tunai di Kasir (`kasir`)**, pelayan dapat memasukkan nominal **Uang Diterima**:
  - Sistem memvalidasi bahwa uang diterima $\ge$ total tagihan.
  - Sistem otomatis menghitung nominal **Kembalian**.
  - Setelah dikonfirmasi, `status_pembayaran` berubah menjadi `lunas` dan `waktu_bayar` tercatat.

### C. Otomatisasi Status Meja & Papan Meja
- Saat pesanan diterima (`terima`), meja terkait otomatis berubah menjadi **`terisi`**.
- Saat pesanan selesai (`selesai`) atau dibatalkan (`batalkan`), meja otomatis kembali menjadi **`tersedia`** apabila tidak ada lagi pesanan aktif (`Pesanan Masuk`, `Diterima`, `Diproses`) pada meja tersebut.
- Halaman **Papan Meja** (`/admin/papan-meja`) menampilkan status seluruh meja secara *real-time* (auto-refresh 15 detik), pesanan aktif, status lunas/belum lunas, serta tombol cepat **Lihat Pesanan** dan **Kosongkan**.

### C. Notifikasi Pesanan Masuk (Suara & Layar)
- Komponen `PesananNotifier` dipasang secara global pada panel melalui `PanelsRenderHook::BODY_END`.
- Melakukan polling ke server setiap **10 detik** (`wire:poll.10s.keep-alive`).
- Jika ada pesanan baru (`status_pesanan = 'Pesanan Masuk'` dengan ID lebih besar dari ID terakhir di session):
  1. Memunculkan **notifikasi layar persisten** di pojok dashboard lengkap dengan nomor meja, nama pengunjung, total tagihan, dan tombol **Lihat Pesanan**.
  2. Memainkan **suara notifikasi ("ding-dong")** menggunakan Web Audio API *(catatan: browser membutuhkan minimal 1 interaksi klik/ketik di halaman agar audio aktif)*.
  3. Memunculkan **Browser Push Notification** (jika izin notifikasi browser diberikan).
  4. Mengedipkan judul tab browser (`🔔 Pesanan Baru!`) apabila tab sedang tidak aktif.
  5. Otomatis menyegarkan tabel pesanan (`pesanan-diperbarui`).

---

## 3. Daftar Routes (Rute Aplikasi)

### Rute Web & Cetak (`routes/web.php`)
| Method | URI | Route Name | Keterangan |
| :--- | :--- | :--- | :--- |
| `GET` | `/` | — | Halaman awal (`welcome`) |
| `GET` | `/admin/pesanans/{pesanan}/struk` | `pesanan.print.struk` | Cetak struk pembayaran kasir (khusus pelayan) |
| `GET` | `/admin/pesanans/{pesanan}/tiket-dapur` | `pesanan.print.tiket-dapur` | Cetak tiket pesanan dapur/bar (khusus pelayan) |

### Rute Panel Pelayan Filament (`/admin`)
Seluruh rute berikut dikelola oleh `AdminPanelProvider` dan dilindungi middleware autentikasi (kecuali halaman login):

| Method | URI | Route Name | Halaman / Fungsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/admin` | `filament.admin.pages.dashboard` | Dashboard utama (Statistik & 3 Chart) |
| `GET` | `/admin/papan-meja` | `filament.admin.pages.papan-meja` | Papan status meja (*Table Status Board*) |
| `GET` | `/admin/login` | `filament.admin.auth.login` | Halaman login (Email atau Username) |
| `POST` | `/admin/logout` | `filament.admin.auth.logout` | Proses logout |
| `GET` | `/admin/pesanans` | `filament.admin.resources.pesanans.index` | Daftar & manajemen pesanan |
| `GET` | `/admin/pesanans/create` | `filament.admin.resources.pesanans.create` | Tambah pesanan manual |
| `GET` | `/admin/pesanans/{record}` | `filament.admin.resources.pesanans.view` | Detail pesanan & daftar item menu dipesan |
| `GET` | `/admin/pesanans/{record}/edit` | `filament.admin.resources.pesanans.edit` | Edit informasi pesanan |
| `GET` | `/admin/menus` | `filament.admin.resources.menus.index` | Daftar menu & stok |
| `GET` | `/admin/menus/create` | `filament.admin.resources.menus.create` | Tambah menu baru |
| `GET` | `/admin/menus/{record}/edit` | `filament.admin.resources.menus.edit` | Edit menu |
| `GET` | `/admin/kategori-menus` | `filament.admin.resources.kategori-menus.index` | Daftar kategori menu |
| `GET` | `/admin/kategori-menus/create` | `filament.admin.resources.kategori-menus.create` | Tambah kategori menu |
| `GET` | `/admin/kategori-menus/{record}/edit` | `filament.admin.resources.kategori-menus.edit` | Edit kategori menu |
| `GET` | `/admin/condiments` | `filament.admin.resources.condiments.index` | Daftar condiment / tambahan |
| `GET` | `/admin/condiments/create` | `filament.admin.resources.condiments.create` | Tambah condiment |
| `GET` | `/admin/condiments/{record}/edit` | `filament.admin.resources.condiments.edit` | Edit condiment |
| `GET` | `/admin/mejas` | `filament.admin.resources.mejas.index` | Daftar meja & QR Code |
| `GET` | `/admin/mejas/create` | `filament.admin.resources.mejas.create` | Tambah meja baru |
| `GET` | `/admin/mejas/{record}/edit` | `filament.admin.resources.mejas.edit` | Edit meja |

### Rute Panel Pemilik Filament (`/pemilik`)
Seluruh rute berikut dikelola oleh `PemilikPanelProvider` dan hanya dapat diakses oleh user dengan `role = 'pemilik'`:

| Method | URI | Route Name | Halaman / Fungsi |
| :--- | :--- | :--- | :--- |
| `GET` | `/pemilik` | `filament.pemilik.pages.dashboard` | Dashboard Pemilik (Omset, Ringkasan & 3 Chart) |
| `GET` | `/pemilik/login` | `filament.pemilik.auth.login` | Halaman login Pemilik (Email atau Username) |
| `POST` | `/pemilik/logout` | `filament.pemilik.auth.logout` | Proses logout Pemilik |
| `GET` | `/pemilik/riwayat-pesanans` | `filament.pemilik.resources.riwayat-pesanans.index` | Log riwayat pesanan & pelaku aksi (*read-only*) |
| `GET` | `/pemilik/riwayat-pesanans/{record}` | `filament.pemilik.resources.riwayat-pesanans.view` | Detail pesanan, item menu & Audit Trail Pelaku Aksi |
| `GET` | `/pemilik/riwayat-pembayarans` | `filament.pemilik.resources.riwayat-pembayarans.index` | Log pembayaran & pelayan pengonfirmasi (*read-only*) |
| `GET` | `/pemilik/monitoring-stok` | `filament.pemilik.resources.monitoring-stok.index` | Pemantauan stok habis & menipis (*read-only*) |

---

## 4. Lokasi File & Struktur Kode

### A. Konfigurasi & Providers
- `app/Providers/Filament/AdminPanelProvider.php` — Konfigurasi panel `/admin` (Pelayan), warna Orange, mode gelap/terang, SPA mode, registrasi widget, dan hook `PesananNotifier`.
- `app/Providers/Filament/PemilikPanelProvider.php` — Konfigurasi panel `/pemilik` (Pemilik), warna Orange, mode gelap/terang, SPA mode, dan widget pemantauan.
- `app/Providers/AppServiceProvider.php` — Registrasi komponen Livewire `pesanan-notifier`.

### B. Eloquent Models, Inheritance & Services PBO
- `app/Models/Pengguna.php` — Abstract parent class (`Authenticatable` + `FilamentUser`), autentikasi `login()`/`logout()` & pembatasan akses panel (`canAccessPanel()` hanya `pelayan`).
- `app/Models/Pelayan.php` — Child class dari `Pengguna` (scope `role = 'pelayan'`), method `kelolaMenu()`, `terimaPesanan()`, `ubahStatusPesanan()`.
- `app/Models/Pemilik.php` — Child class dari `Pengguna` (scope `role = 'pemilik'`), method `lihatDashboard()`, `lihatRingkasanTransaksi()`.
- `app/Models/User.php` — Mewarisi `Pengguna` untuk autentikasi default Laravel.
- `app/Contracts/MetodePembayaran.php` — Interface pembayaran (`prosesPembayaran()`, `getNamaMetode()`).
- `app/Services/Pembayaran/PembayaranKasir.php` & `PembayaranOnline.php` — Implementasi pembayaran tunai kasir (dengan kembalian) dan online.
- `app/Models/Meja.php`, `KategoriMenu.php`, `Menu.php`, `Condiment.php`, `Pesanan.php`, `DetailPesanan.php`, `Pembayaran.php`, `Keranjang.php`, `KeranjangItem.php`.

### C. Filament Pages & Resources (`app/Filament/`)
- `Pages/Auth/Login.php` — Halaman login kustom menerima **Email atau Username**.
- `Pages/PapanMeja.php` & `resources/views/filament/pages/papan-meja.blade.php` — Papan status seluruh meja cafe secara *real-time*.
- `Resources/Pesanans/`
  - `PesananResource.php` — Badge jumlah `Pesanan Masuk` (di-cache 10 detik), konfigurasi `infolist()`, dan registrasi `DetailPesanansRelationManager`.
  - `Pages/ListPesanans.php` — Tab filter cepat (`Semua`, `Pesanan Masuk`, `Diterima`, `Diproses`, `Selesai`, `Dibatalkan`, `Belum Lunas`) dan listener `pesanan-diperbarui`.
  - `Pages/ViewPesanan.php` & `Schemas/PesananInfolist.php` — Halaman khusus **Detail Pesanan** + tombol **Cetak Struk** & **Tiket Dapur**.
  - `Pages/CreatePesanan.php`, `Pages/EditPesanan.php`, & `Schemas/PesananForm.php` — Form buat/edit pesanan lengkap dengan `Repeater` item menu (`detailPesanans`) & auto-kalkulasi subtotal/total.
  - `Tables/PesanansTable.php` — Polling 15 detik, aksi `Detail`, `Terima`, `Proses`, `Selesai`, `Bayar`, `Cetak Struk`, `Tiket Dapur`, `Edit`, `Batalkan` (tanpa aksi Delete).
  - `RelationManagers/DetailPesanansRelationManager.php` — Rincian item menu khusus pada `ViewPesanan`.
- `Resources/Menus/` — `ToggleColumn` ketersediaan menu, tombol **Restock Cepat**, upload gambar (`disk('public')`), pilihan multi-condiment, badge warna stok.
- `Resources/KategoriMenus/` — Pengelolaan kategori beserta hitung jumlah menu.
- `Resources/Condiments/` — `ToggleColumn` ketersediaan condiment langsung di tabel.
- `Resources/Mejas/` — Generator QR Code (`QR-XXXXXXXX`), tombol **Generate Ulang**, aksi **QR Code** (**Unduh PNG / SVG**), serta tombol **Kosongkan Meja**.
- `Pemilik/` (Khusus Panel `/pemilik`, *Read-Only*)
  - `Resources/RiwayatPesananResource.php` (+ `Pages/ListRiwayatPesanans.php`, `Pages/ViewRiwayatPesanan.php`) — Menampilkan riwayat pesanan lengkap dengan **Audit Trail Pelaku Aksi** (`Diterima Oleh`, `Konfirmasi Bayar Oleh`, `Dibatalkan Oleh`, `Waktu Dibatalkan`).
  - `Resources/RiwayatPembayaranResource.php` (+ `Pages/ListRiwayatPembayarans.php`) — Menampilkan log pembayaran dan siapa pelayan yang mengonfirmasi (`dikonfirmasi_oleh`).
  - `Resources/MonitoringStokResource.php` (+ `Pages/ListMonitoringStoks.php`) — Pemantauan stok habis/hampir habis.
  - `Widgets/PemilikStatsOverview.php` — 4 kartu metrik ringkasan transaksi & stok untuk pemilik.

### D. Widgets, Views & Assets
- `app/Filament/Widgets/PesananStatsOverview.php` — 4 kartu metrik utama (Pesanan Baru Masuk, Sedang Diproses, Pendapatan Hari Ini, Menu Menipis/Habis).
- `app/Filament/Widgets/PendapatanChart.php` — Grafik garis & batang pendapatan vs jumlah pesanan (filter 7, 14, dan 30 hari terakhir).
- `app/Filament/Widgets/StatusPesananChart.php` — Grafik donat distribusi status pesanan hari ini.
- `app/Filament/Widgets/MenuTerlarisChart.php` — Grafik batang horizontal Top 5 menu terlaris.
- `app/Filament/Widgets/PesananNotifier.php` & `resources/views/filament/widgets/pesanan-notifier.blade.php` — Pemantau pesanan baru + notifikasi layar & suara.
- `resources/views/filament/modals/meja-qr-code.blade.php` & `public/js/qrcode.min.js` — Modal preview QR Code meja berbasis offline-ready Canvas/SVG dengan fitur unduh PNG & SVG.
- `resources/views/print/struk-pesanan.blade.php` & `resources/views/print/tiket-dapur.blade.php` — Template cetak struk pelanggan & tiket dapur.
- `resources/views/vendor/filament/` — Override view pagination & table selection agar tidak bergantung pada ekstensi PHP `intl`.

### E. Database & Pengujian
- `database/migrations/` — 17 file migrasi (3 bawaan Laravel + 14 migrasi sistem cafe termasuk `add_audit_trail_to_pesanans_and_pembayarans_table`).
- `database/seeders/DatabaseSeeder.php` — Data awal akun, 10 meja (`M01`–`M10`), 3 kategori, 5 condiment, 9 menu, dan 4 contoh pesanan.
- `tests/Feature/PelayanFilamentTest.php` — 22 automated feature tests untuk panel Pelayan.
- `tests/Feature/PemilikFilamentTest.php` — 4 automated feature tests untuk panel Pemilik (akses role, read-only enforcement, dan audit trail pelaku aksi).

---

## 5. Skema Tabel Database

```text
users (id, name, username, email, password, role)
mejas (id, nomor_meja, qr_code, status, kapasitas)
kategori_menus (id, nama_kategori, deskripsi)
menus (id, kategori_menu_id -> kategori_menus, nama_menu, deskripsi, harga, stok, gambar, is_aktif)
condiments (id, nama_condiment, harga_condiment, status_ketersediaan)
menu_condiment (id, menu_id -> menus, condiment_id -> condiments)

pesanans (id, nomor_pesanan, meja_id -> mejas, nama_pengunjung, tanggal_pesanan, status_pesanan, total_harga, pelayan_id -> users, catatan)
detail_pesanans (id, pesanan_id -> pesanans, menu_id -> menus, harga_satuan, jumlah, catatan, subtotal)
detail_pesanan_condiment (id, detail_pesanan_id -> detail_pesanans, condiment_id -> condiments, harga_condiment)
pembayarans (id, pesanan_id -> pesanans, metode_pembayaran, total_bayar, status_pembayaran, transaksi_ref, waktu_bayar)

keranjangs (id, meja_id -> mejas, session_id, nama_pengunjung, total_harga, tanggal_dibuat)
keranjang_items (id, keranjang_id -> keranjangs, menu_id -> menus, harga_satuan, jumlah, catatan, subtotal)
keranjang_item_condiment (id, keranjang_item_id -> keranjang_items, condiment_id -> condiments, harga_condiment)
```

---

## 6. Aturan Pengembangan untuk Tim

1. **Konvensi Filament v5:**
   - Komponen layout `Section` harus di-import dari `Filament\Schemas\Components\Section` (bukan `Filament\Forms\Components\Section`).
   - Properti `$navigationGroup` pada class Resource wajib bertipe `protected static string|UnitEnum|null`.
   - Untuk format angka/Rupiah di kolom tabel atau teks, gunakan helper `number_format(..., 0, ',', '.')` daripada `->money()` atau `Number::format()` apabila server lokal belum mengaktifkan ekstensi PHP `ext-intl`.
2. **Performa & Driver `.env`:**
   - Pastikan `SESSION_DRIVER=file` dan `CACHE_STORE=file` tetap digunakan di `.env` lokal agar perpindahan halaman tidak terhambat oleh query session/cache ke server database remote (Supabase).
3. **Upload Gambar Menu:**
   - Pastikan symbolic link storage sudah aktif dengan menjalankan:
     ```bash
     php artisan storage:link
     ```
4. **Kualitas Kode & Pengujian:**
   - Sebelum commit/push perubahan file PHP, rapikan format kode dengan Laravel Pint:
     ```bash
     vendor/bin/pint --dirty --format agent
     ```
   - Jalankan test suite untuk memastikan tidak ada regresi:
     ```bash
     php artisan test --compact tests/Feature/PelayanFilamentTest.php
     ```
5. **Perintah Migrasi & Seeding Ulang:**
   - Untuk mengulang database dari awal beserta data contoh:
     ```bash
     php artisan migrate:fresh --seed --force
     ```
6. **Cache Komponen Filament & Custom View:**
   - Setiap kali menambah atau mengubah *Page*/*Resource* baru (misal `PapanMeja`), bersihkan cache komponen agar tidak terjadi 404 atau menu navigasi tidak muncul:
     ```bash
     php artisan filament:optimize-clear && php artisan optimize:clear
     ```
   - Untuk halaman Blade kustom di Filament (seperti `papan-meja.blade.php`), gunakan CSS ter-scope atau komponen bawaan `<x-filament::*>` agar grid dan tema gelap/terang tampil konsisten tanpa bergantung pada rebuild CSS Filament.
