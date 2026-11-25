import assert from 'node:assert/strict'
import test from 'node:test'
import { BaseEntity } from '../src/models/BaseEntity.js'
import { Condiment } from '../src/models/Condiment.js'
import { KategoriMenu } from '../src/models/KategoriMenu.js'
import { Meja } from '../src/models/Meja.js'
import { MenuItem } from '../src/models/MenuItem.js'
import { PembayaranInfo } from '../src/models/PembayaranInfo.js'
import { Pesanan } from '../src/models/Pesanan.js'
import { ShoppingCart } from '../src/models/ShoppingCart.js'

test('BaseEntity bersifat abstrak dan diwarisi oleh seluruh model domain', () => {
  assert.throws(() => new BaseEntity(1), TypeError)

  const meja = new Meja({ id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01' })
  const kategori = new KategoriMenu({ id: 1, nama_kategori: 'Minuman' })
  const condiment = new Condiment({ id: 2, nama_condiment: 'Extra Shot', harga_condiment: 5000 })
  const menu = new MenuItem({ id: 3, nama_menu: 'Es Kopi Susu', harga: 18000 })
  const pembayaran = new PembayaranInfo({ metode_pembayaran: 'kasir', total_bayar: 18000 })
  const pesanan = new Pesanan({ id: 4, nomor_pesanan: 'ORD-20261125-ABCDE' })

  assert.ok(meja instanceof BaseEntity)
  assert.ok(kategori instanceof BaseEntity)
  assert.ok(condiment instanceof BaseEntity)
  assert.ok(menu instanceof BaseEntity)
  assert.ok(pembayaran instanceof BaseEntity)
  assert.ok(pesanan instanceof BaseEntity)
})

test('MenuItem mengekspos status stok tanpa angka stok dan memfilter kategori/nama', () => {
  const menuTersedia = MenuItem.fromApi({
    id: 1,
    nama_menu: 'Es Kopi Susu',
    harga: 18000,
    stok_habis: false,
    bisa_dipesan: true,
    status_teks: 'Tersedia',
    kategori: { id: 10, nama_kategori: 'Minuman' },
    condiments: [
      { id: 1, nama_condiment: 'Extra Shot', harga_condiment: 5000, tersedia: true },
      { id: 2, nama_condiment: 'Es Batu', harga_condiment: 0, tersedia: true },
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
  assert.equal(menuHabis.isTersedia(), false)
  assert.equal(menuTersedia.hasCondiments(), true)
  assert.equal(menuTersedia.condiments[0].getFormattedHarga(), '+Rp\u00a05.000')
  assert.equal(menuTersedia.condiments[1].getFormattedHarga(), 'Gratis')
  assert.equal(menuTersedia.matchesFilter(10, 'kopi'), true)
  assert.equal(menuTersedia.matchesFilter(99, 'kopi'), false)
})

test('ShoppingCart menggabungkan item identik, menghitung total, dan membangun payload checkout Laravel', () => {
  const meja1 = Meja.fromApi({ id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01', status: 'tersedia' })
  const meja2 = Meja.fromApi({ id: 2, nomor_meja: 'M02', qr_code: 'QR-MEJA-02', status: 'tersedia' })

  const extraShot = Condiment.fromApi({ id: 1, nama_condiment: 'Extra Shot', harga_condiment: 5000 })
  const gulaAren = Condiment.fromApi({ id: 2, nama_condiment: 'Gula Aren', harga_condiment: 3000 })

  const kopi = MenuItem.fromApi({ id: 5, nama_menu: 'Es Kopi Susu', harga: 18000 })

  let cart = new ShoppingCart().withMeja(meja1)
  cart = cart.addItem({ menu: kopi, jumlah: 1, condiments: [extraShot, gulaAren], catatan: 'Sedikit es' })
  // Urutan condiment berbeda tapi item + catatan sama -> harus digabung menjadi jumlah 3
  cart = cart.addItem({ menu: kopi, jumlah: 2, condiments: [gulaAren, extraShot], catatan: ' Sedikit es ' })
  // Catatan berbeda -> baris terpisah
  cart = cart.addItem({ menu: kopi, jumlah: 1, condiments: [], catatan: 'Normal' })

  assert.equal(cart.lines.length, 2)
  assert.equal(cart.getTotalItems(), 4)
  // Baris 1: (18000 + 5000 + 3000) * 3 = 78000; Baris 2: 18000 * 1 = 18000 -> Total 96000
  assert.equal(cart.getTotalHarga(), 96000)

  const payload = cart.toCheckoutPayload({
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

test('Pesanan mengenali status final dan kebutuhan sinkronisasi Midtrans', () => {
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
})
