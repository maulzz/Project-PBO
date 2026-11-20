# Dokumentasi Penerapan OOP / PBO (Pemrograman Berorientasi Objek)

Dokumen ini memetakan penerapan konsep **Pemrograman Berorientasi Objek (OOP/PBO)** pada sistem Pelayan Cafe sesuai rancangan proposal (Bab 8.3) beserta lokasi file dan method-nya untuk memudahkan pelacakan kode oleh tim.

---

## 1. Inheritance (Pewarisan) — `Pengguna` $\rightarrow$ `Pelayan` & `Pemilik`

Sesuai rancangan Bab 8.3.A di proposal, dibuat kelas induk abstrak `Pengguna` yang menyediakan identitas dan autentikasi bersama, lalu diturunkan ke kelas `Pelayan`, `Pemilik`, dan `User`.

```text
Illuminate\Foundation\Auth\User (Authenticatable)
        ▲
        │ (extends + implements FilamentUser)
App\Models\Pengguna  [abstract class]
        ▲
        ├── App\Models\Pelayan  (Global Scope: role = 'pelayan')
        ├── App\Models\Pemilik  (Global Scope: role = 'pemilik')
        └── App\Models\User     (Model autentikasi default Laravel)
```

### Lokasi File & Rincian Method
| Kelas | Lokasi File | Atribut / Method Utama |
| :--- | :--- | :--- |
| **`Pengguna` (Abstract Parent)** | `app/Models/Pengguna.php` | Atribut: `name`, `username`, `email`, `password`, `role`<br>Method: `canAccessPanel()`, `isPelayan()`, `isPemilik()`, `login()`, `logout()` |
| **`Pelayan` (Child Class)** | `app/Models/Pelayan.php` | Global scope `role = 'pelayan'`<br>Method: `kelolaMenu()`, `terimaPesanan()`, `ubahStatusPesanan()`, `pesanans()` |
| **`Pemilik` (Child Class)** | `app/Models/Pemilik.php` | Global scope `role = 'pemilik'`<br>Method: `lihatDashboard()`, `lihatRingkasanTransaksi()` |
| **`User` (Child Class)** | `app/Models/User.php` | Mewarisi `Pengguna` untuk kompatibilitas `UserFactory` & `config/auth.php` |

### Titik Pemakaian di Sistem
- **Autentikasi & Hak Akses (`Pengguna::canAccessPanel`)**: Dipanggil otomatis oleh Laravel Filament saat mengakses panel `/admin` (hanya `isPelayan()` yang diizinkan masuk).
- **Proses Penerimaan & Perubahan Status Pesanan**: `app/Filament/Resources/Pesanans/Tables/PesanansTable.php` memanggil `Pelayan::find(Auth::id())?->terimaPesanan($record)` dan `ubahStatusPesanan($record, $status)`.

---

## 2. Interface & Polymorphism — `MetodePembayaran`

Sesuai rancangan Bab 8.3.B di proposal, pembayaran dimodelkan menggunakan interface `MetodePembayaran` sehingga metode pembayaran berbeda dapat diproses secara seragam (*polymorphic*).

```text
«interface» App\Contracts\MetodePembayaran
   + prosesPembayaran(float $totalBayar): bool
   + getNamaMetode(): string
        ▲
        ├── App\Services\Pembayaran\PembayaranKasir   (Tunai di kasir + hitung kembalian)
        └── App\Services\Pembayaran\PembayaranOnline  (QRIS / Transfer / Payment Gateway)
```

### Lokasi File & Rincian Method
| Komponen | Lokasi File | Peran & Method |
| :--- | :--- | :--- |
| **`MetodePembayaran` (Interface)** | `app/Contracts/MetodePembayaran.php` | Kontrak `prosesPembayaran(float $totalBayar): bool` dan `getNamaMetode(): string` |
| **`PembayaranKasir` (Implementasi)** | `app/Services/Pembayaran/PembayaranKasir.php` | Validasi `uangDiterima >= totalBayar`, simpan status `lunas`, hitung `getKembalian()` |
| **`PembayaranOnline` (Implementasi)** | `app/Services/Pembayaran/PembayaranOnline.php` | Mencatat `transaksi_ref` pembayaran online & mengubah status menjadi `lunas` |

### Titik Pemakaian di Sistem
- Di `app/Filament/Resources/Pesanans/Tables/PesanansTable.php` pada method `konfirmasiBayarAction()`:
  ```php
  /** @var MetodePembayaran $metode */
  $metode = $data['metode_pembayaran'] === 'online'
      ? new PembayaranOnline($record, $data['transaksi_ref'] ?? null)
      : new PembayaranKasir($record, (float) ($data['uang_diterima'] ?? 0));

  $metode->prosesPembayaran((float) $record->total_harga);
  ```

---

## 3. Encapsulation & Domain Methods pada Model Entitas

Setiap model entitas di `app/Models/` membungkus (*encapsulate*) state, casting tipe data, dan aturan bisnisnya masing-masing:

| Model | Lokasi File | Method Bisnis (Domain Logic) |
| :--- | :--- | :--- |
| **`Menu`** | `app/Models/Menu.php` | `isStokTersedia()`, `kurangiStok(int $jumlah)` |
| **`Meja`** | `app/Models/Meja.php` | `isTersedia()` |
| **`Pesanan`** | `app/Models/Pesanan.php` | `hitungTotalHarga()`, `isDapatDibatalkan()` |
| **`DetailPesanan`** | `app/Models/DetailPesanan.php` | `hitungSubtotal()` |
| **`Pembayaran`** | `app/Models/Pembayaran.php` | `isLunas()` |

---

## 4. Relasi Antarkelas (Association & Composition)

| Relasi | Tipe | Implementasi Eloquent |
| :--- | :--- | :--- |
| `KategoriMenu` $\leftrightarrow$ `Menu` | Asosiasi ($1 : 0..*$) | `hasMany(Menu::class)` / `belongsTo(KategoriMenu::class)` |
| `Menu` $\leftrightarrow$ `Condiment` | Asosiasi ($0..* : 0..*$) | `belongsToMany(Condiment::class, 'menu_condiment')` |
| `Pesanan` $\leftrightarrow$ `DetailPesanan` | Komposisi ($1 : 1..*$) | `hasMany(DetailPesanan::class)` + `cascadeOnDelete()` di migrasi |
| `DetailPesanan` $\leftrightarrow$ `Condiment` | Asosiasi ($0..* : 0..*$) | `belongsToMany(Condiment::class, 'detail_pesanan_condiment')->withPivot('harga_condiment')` |
| `Pesanan` $\leftrightarrow$ `Meja` | Asosiasi ($0..* : 1$) | `belongsTo(Meja::class)` / `hasMany(Pesanan::class)` |
| `Pesanan` $\leftrightarrow$ `Pembayaran` | Asosiasi ($1 : 0..1$) | `hasOne(Pembayaran::class)` / `belongsTo(Pesanan::class)` |
| `Keranjang` $\leftrightarrow$ `KeranjangItem` | Komposisi ($1 : 0..*$) | `hasMany(KeranjangItem::class)` + `cascadeOnDelete()` |

---

## 5. Pengujian Unit/Fitur OOP

Pengujian penerapan inheritance (`Pengguna`, `Pelayan`, `Pemilik`) dan interface (`MetodePembayaran`, `PembayaranKasir`, `PembayaranOnline`) terdapat pada:
- `tests/Feature/PelayanFilamentTest.php` (`test_penerapan_oop_inheritance_dan_interface_pembayaran`)
