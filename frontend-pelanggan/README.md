# Frontend Pelanggan — Sistem Pemesanan Cafe Berbasis QR Code (PBO)

Aplikasi **React + Vite + Tailwind CSS** untuk aktor **Pengunjung Cafe/Warkop** sesuai dokumen **Proposal Proyek PBO** dan spesifikasi REST API **`Pelayan-Laravel/DOKUMENTASI.md`**.

## Kesesuaian Kebutuhan Fungsional & Aturan Bisnis (PDF & Backend)

- **`KF-01` & `KF-02` (Scan QR & Identifikasi Meja):** Membaca parameter QR Code dari URL (`/order?meja=<qr_code>`, `/?meja=<qr_code>`, atau `/order/<qr_code>`) dan memvalidasi ke endpoint `GET /api/v1/meja/{qrCode}`. Pengunjung hanya dapat memesan setelah QR meja tervalidasi (**Batasan 4.3.1**).
- **`KF-03` (Katalog Menu Lengkap):** Menampilkan daftar menu beserta **nama, deskripsi, kategori, dan harga menu**, lengkap dengan filter kategori (`GET /api/v1/kategoris`) dan pencarian (`GET /api/v1/menus`).
- **`KF-04`, `KF-05`, `KF-06` (Keranjang, Condiment, & Catatan Khusus):** Mendukung pemilihan tambahan (*condiment*) beserta harga tambahannya, penambahan/perubahan catatan khusus per item menu (baik saat memilih menu maupun di halaman keranjang), pengaturan jumlah (`1–99`), dan penghapusan item.
- **`KF-07` & `KF-08` (Identitas Pengunjung & Nomor Pesanan Unik):** Mewajibkan pengisian nama pengunjung sebelum checkout (`POST /api/v1/pesanans`) dan menghasilkan/menampilkan nomor pesanan unik (`ORD-YYYYMMDD-XXXXX`).
- **`KF-09` & `Batasan 4.3.1` (Proteksi Stok Habis Tanpa Ekspos Angka Stok):** Tidak menampilkan jumlah angka stok kepada pengunjung. Menu yang habis menampilkan badge **`Stok Habis`** dan otomatis dinonaktifkan baik di antarmuka maupun di level model OOP (`ShoppingCart.addItem`).
- **`KF-10`, `KF-11`, & `KF-14` (Pembayaran Online / Kasir & Pelacakan Status):**
  - Mendukung metode **`kasir`** (instruksi pembayaran di kasir dengan menyebutkan nomor pesanan unik) dan **`online`** (integrasi Midtrans Snap Sandbox + sinkronisasi `POST /api/v1/pesanans/{nomorPesanan}/cek-pembayaran`).
  - Melacak tahapan status pesanan secara *real-time* (`Pesanan Masuk` → `Diterima` → `Diproses` → `Selesai` / `Dibatalkan`) dan menyimpan riwayat pesanan pengunjung di browser (**`KNF-04`**).

## Struktur Model OOP Frontend (`src/models/` & `src/services/`)

Mengimplementasikan rancangan kelas pada **Bab 7 & Bab 8 Proposal PBO**:
- `BaseEntity` — Kelas abstrak dasar seluruh entitas domain.
- `Pengunjung` — Mengenkapsulasi `namaPengunjung`, asosiasi `1:1` dengan `Keranjang`, asosiasi `1:0..*` dengan `Pesanan`, serta method `lihatMenu()` dan `buatPesanan()`.
- `Meja` — Atribut `idMeja`, `nomorMeja`, `qrCode`, serta method `identifikasiMeja()`.
- `KategoriMenu` — Atribut `idKategori`, `namaKategori`, `deskripsi`, serta method `tampilkanKategori()`.
- `MenuItem` (`Menu`) — Atribut `idMenu`, `namaMenu`, `deskripsi`, `harga`, `stokHabis`, `statusTeks`, serta method `tampilkanMenu()` dan `cekStok()`.
- `Condiment` — Atribut `idCondiment`, `namaCondiment`, `hargaCondiment`, `statusKetersediaan`, serta method `cekKetersediaan()`.
- `ShoppingCart` (`Keranjang`) & `CartItem` (`DetailPesanan`) — Method `tambahItem()`, `hapusItem()`, `ubahJumlah()`, `tambahCatatan()`, `hitungSubtotal()`, `hitungTotal()`, dan `buatPesanan()`.
- `Pesanan` & `PembayaranInfo` (`Pembayaran`) — Pelacakan tahapan status pesanan dan status pembayaran.
- `MetodePembayaranStrategy` (`MetodePembayaran`), `PembayaranOnlineStrategy` (`PembayaranOnline`), `PembayaranKasirStrategy` (`PembayaranKasir`), & `PembayaranFactory` — Polimorfisme pemrosesan pembayaran di sisi frontend.
