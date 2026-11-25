import assert from 'node:assert/strict'
import test from 'node:test'
import { BaseEntity } from '../src/models/BaseEntity.js'
import { CartItem, DetailPesanan } from '../src/models/CartItem.js'
import { Condiment } from '../src/models/Condiment.js'
import { KategoriMenu } from '../src/models/KategoriMenu.js'
import { Meja } from '../src/models/Meja.js'
import { Menu, MenuItem } from '../src/models/MenuItem.js'
import { Pembayaran, PembayaranInfo } from '../src/models/PembayaranInfo.js'
import { Pengunjung } from '../src/models/Pengunjung.js'
import { Pesanan } from '../src/models/Pesanan.js'
import { Keranjang, ShoppingCart } from '../src/models/ShoppingCart.js'

test('BaseEntity bersifat abstrak dan diwarisi oleh seluruh model domain sesuai Bab 7 & 8 PDF', () => {
  assert.throws(() => new BaseEntity(1), TypeError)

  const meja = new Meja({ id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01' })
  const kategori = new KategoriMenu({ id: 1, nama_kategori: 'Minuman', deskripsi: 'Minuman segar' })
  const condiment = new Condiment({ id: 2, nama_condiment: 'Extra Shot', harga_condiment: 5000 })
  const menu = new Menu({ id: 3, nama_menu: 'Es Kopi Susu', harga: 18000 })
  const pembayaran = new Pembayaran({ id: 7, metode_pembayaran: 'kasir', total_bayar: 18000 })
  const pesanan = new Pesanan({ id: 4, nomor_pesanan: 'ORD-20261125-ABCDE' })
  const pengunjung = new Pengunjung({ id: 9, namaPengunjung: 'Wildan' })

  assert.ok(meja instanceof BaseEntity)
  assert.ok(kategori instanceof BaseEntity)
  assert.ok(condiment instanceof BaseEntity)
  assert.ok(menu instanceof BaseEntity)
  assert.ok(pembayaran instanceof BaseEntity)
  assert.ok(pesanan instanceof BaseEntity)
  assert.ok(pengunjung instanceof BaseEntity)

  assert.equal(meja.idMeja, 1)
  assert.equal(meja.identifikasiMeja('QR-MEJA-01'), true)
  assert.equal(meja.identifikasiMeja('QR-LAIN'), false)
  assert.equal(kategori.idKategori, 1)
  assert.deepEqual(kategori.tampilkanKategori(), {
    idKategori: 1,
    namaKategori: 'Minuman',
    deskripsi: 'Minuman segar',
  })
  assert.equal(condiment.idCondiment, 2)
  assert.equal(condiment.cekKetersediaan(), true)
  assert.equal(menu.idMenu, 3)
  assert.equal(pembayaran.idPembayaran, 7)
  assert.equal(pesanan.idPesanan, 4)
})

test('MenuItem / Menu mengekspos status stok tanpa angka stok (Batasan 4.3.1), cekStok(), tampilkanMenu(), dan memfilter kategori/nama', () => {
  const menuTersedia = MenuItem.fromApi({
    id: 1,
    nama_menu: 'Es Kopi Susu',
    deskripsi: 'Kopi susu gula aren',
    harga: 18000,
    stok_habis: false,
    bisa_dipesan: true,
    status_teks: 'Tersedia',
    kategori: { id: 10, nama_kategori: 'Minuman' },
    condiments: [
      { id: 1, nama_condiment: 'Extra Shot', harga_condiment: 5000, tersedia: true },
      { id: 2, nama_condiment: 'Es Batu', harga_condiment: 0, tersedia: true },
      { id: 3, nama_condiment: 'Sirup Habis', harga_condiment: 3000, status_ketersediaan: false },
    ],
  })

  const menuHabis = MenuItem.fromApi({
    id: 2,
    nama_menu: 'Matcha Latte',
    harga: 22000,
    stok_habis: true,
    bisa_dipesan: false,
    status_teks: 'Stok Habis',
    kategori: { id: 10, nama_kategori: 'Minuman' },
  })

  assert.equal(menuTersedia.isTersedia(), true)
  assert.equal(menuTersedia.cekStok(), true)
  assert.equal(menuHabis.isTersedia(), false)
  assert.equal(menuHabis.cekStok(), false)
  assert.equal(menuTersedia.namaKategori, 'Minuman')
  assert.equal(menuTersedia.hasCondiments(), true)
  assert.equal(menuTersedia.getAvailableCondiments().length, 2)
  assert.equal(menuTersedia.condiments[0].getFormattedHarga(), '+Rp\u00a05.000')
  assert.equal(menuTersedia.condiments[1].getFormattedHarga(), 'Gratis')
  assert.equal(menuTersedia.matchesFilter(10, 'kopi'), true)
  assert.equal(menuTersedia.matchesFilter(10, 'gula aren'), true)
  assert.equal(menuTersedia.matchesFilter(99, 'kopi'), false)

  const infoMenu = menuTersedia.tampilkanMenu()
  assert.equal(infoMenu.namaMenu, 'Es Kopi Susu')
  assert.equal(infoMenu.kategori, 'Minuman')
  assert.equal('stok' in menuTersedia.toJSON(), false)
})

test('ShoppingCart / Keranjang menolak menu stok habis (KF-09), menggabungkan item identik, mendukung ubahCatatan(), dan membangun payload checkout Laravel', () => {
  const meja1 = Meja.fromApi({ id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01', status: 'tersedia' })
  const meja2 = Meja.fromApi({ id: 2, nomor_meja: 'M02', qr_code: 'QR-MEJA-02', status: 'tersedia' })

  const extraShot = Condiment.fromApi({ id: 1, nama_condiment: 'Extra Shot', harga_condiment: 5000 })
  const gulaAren = Condiment.fromApi({ id: 2, nama_condiment: 'Gula Aren', harga_condiment: 3000 })

  const kopi = MenuItem.fromApi({ id: 5, nama_menu: 'Es Kopi Susu', harga: 18000 })
  const menuHabis = MenuItem.fromApi({
    id: 9,
    nama_menu: 'Menu Kosong',
    harga: 20000,
    stok_habis: true,
    bisa_dipesan: false,
    status_teks: 'Stok Habis',
  })

  let cart = new Keranjang().withMeja(meja1)

  // KF-09: Menu stok habis tidak boleh masuk keranjang
  assert.throws(
    () => cart.tambahMenu({ menu: menuHabis, jumlah: 1 }),
    /sedang habis dan tidak dapat dipesan/,
  )

  cart = cart.tambahItem({ menu: kopi, jumlah: 1, condiments: [extraShot, gulaAren], catatan: 'Sedikit es' })
  // Urutan condiment berbeda tapi item + catatan sama -> harus digabung menjadi jumlah 3
  cart = cart.addItem({ menu: kopi, jumlah: 2, condiments: [gulaAren, extraShot], catatan: ' Sedikit es ' })
  // Catatan berbeda -> baris terpisah
  cart = cart.addItem({ menu: kopi, jumlah: 1, condiments: [], catatan: 'Normal' })

  assert.equal(cart.lines.length, 2)
  assert.equal(cart.getTotalItems(), 4)
  // Baris 1: (18000 + 5000 + 3000) * 3 = 78000; Baris 2: 18000 * 1 = 18000 -> Total 96000
  assert.equal(cart.getTotalHarga(), 96000)
  assert.equal(cart.hitungTotal(), 96000)

  // Ubah catatan pada baris kedua melalui method OOP
  const keyBaris2 = cart.lines[1].key
  const cartUpdatedNote = cart.ubahCatatan(keyBaris2, 'Tanpa gula')
  assert.equal(cartUpdatedNote.lines[1].catatan, 'Tanpa gula')

  // DetailPesanan / CartItem methods
  const detail = new DetailPesanan({ menu: kopi, jumlah: 2, condiments: [extraShot], catatan: 'Es sedikit' })
  assert.equal(detail.hitungSubtotal(), 46000)
  assert.equal(detail.ubahJumlah(3).jumlah, 3)
  assert.equal(detail.tambahCatatan('Gula sedikit').catatan, 'Gula sedikit')

  const payload = cart.buatPesanan({
    namaPengunjung: 'Budi',
    metodePembayaran: 'kasir',
    catatan: 'Antar cepat',
  })

  assert.deepEqual(payload, {
    meja_id: 1,
    nama_pengunjung: 'Budi',
    metode_pembayaran: 'kasir',
    catatan: 'Antar cepat',
    items: [
      {
        menu_id: 5,
        jumlah: 3,
        catatan: 'Sedikit es',
        condiment_ids: [1, 2],
      },
      {
        menu_id: 5,
        jumlah: 1,
        catatan: 'Normal',
        condiment_ids: [],
      },
    ],
  })

  // Serialisasi -> deserialisasi mempertahankan state
  const restored = ShoppingCart.deserialize(cart.serialize())
  assert.equal(restored.meja.nomorMeja, 'M01')
  assert.equal(restored.getTotalHarga(), 96000)

  // Pindah meja harus mengosongkan keranjang
  const switched = restored.withMeja(meja2)
  assert.equal(switched.meja.id, 2)
  assert.equal(switched.isEmpty(), true)
})

test('Pesanan & Pengunjung mengenali tahapan status (KF-14), batasan pembatalan (Batasan 4.3.1), dan sinkronisasi Midtrans', async () => {
  const onlineMenunggu = Pesanan.fromApi({
    id: 10,
    nomor_pesanan: 'ORD-20261125-00001',
    status_pesanan: 'Pesanan Masuk',
    total_harga: 25000,
    pembayaran: {
      metode_pembayaran: 'online',
      status_pembayaran: 'menunggu',
      snap_token: 'snap-123',
    },
  })

  assert.equal(onlineMenunggu.isFinal(), false)
  assert.equal(onlineMenunggu.needsMidtransSync(), true)
  assert.equal(onlineMenunggu.pembayaran.hasSnapPayment(), true)
  assert.equal(onlineMenunggu.isDapatDibatalkan(), true)
  assert.equal(onlineMenunggu.canCancelMandiri(), false)
  assert.equal(onlineMenunggu.getTahapanIndex(), 0)

  const selesaiLunas = Pesanan.fromApi({
    id: 11,
    nomor_pesanan: 'ORD-20261125-00002',
    status_pesanan: 'Selesai',
    total_harga: 25000,
    pembayaran: {
      metode_pembayaran: 'online',
      status_pembayaran: 'lunas',
    },
  })

  assert.equal(selesaiLunas.isFinal(), true)
  assert.equal(selesaiLunas.needsMidtransSync(), false)
  assert.equal(selesaiLunas.isDapatDibatalkan(), false)
  assert.equal(selesaiLunas.getTahapanIndex(), 3)

  const dibatalkan = Pesanan.fromApi({
    id: 12,
    nomor_pesanan: 'ORD-20261125-00003',
    status_pesanan: 'Dibatalkan',
    total_harga: 25000,
    pembayaran: {
      metode_pembayaran: 'kasir',
      status_pembayaran: 'dibatalkan',
    },
  })

  assert.equal(dibatalkan.isFinal(), true)
  assert.equal(dibatalkan.getTahapanIndex(), -1)

  // Uji kelas Pengunjung (lihatMenu, buatPesanan, lihatRiwayatPesanan)
  const meja = Meja.fromApi({ id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01' })
  const menu = MenuItem.fromApi({ id: 5, nama_menu: 'Es Kopi Susu', harga: 18000 })
  const keranjang = new ShoppingCart().withMeja(meja).addItem({ menu, jumlah: 2 })
  let pengunjung = new Pengunjung({ namaPengunjung: 'Cahyo', keranjang })

  const mockApi = {
    async getMenus() {
      return [menu]
    },
    async createPesanan(payload) {
      assert.equal(payload.nama_pengunjung, 'Cahyo')
      assert.equal(payload.meja_id, 1)
      return onlineMenunggu
    },
  }

  const daftarMenu = await pengunjung.lihatMenu(mockApi)
  assert.equal(daftarMenu.length, 1)

  const pesananBaru = await pengunjung.buatPesanan(mockApi, { metodePembayaran: 'online' })
  pengunjung = pengunjung.withPesananBaru(pesananBaru)
  assert.equal(pengunjung.lihatRiwayatPesanan().length, 1)
  assert.equal(pengunjung.lihatRiwayatPesanan()[0].nomorPesanan, 'ORD-20261125-00001')
})
