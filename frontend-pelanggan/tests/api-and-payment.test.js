import assert from 'node:assert/strict'
import test from 'node:test'
import { Meja } from '../src/models/Meja.js'
import { Pesanan } from '../src/models/Pesanan.js'
import { CafeApiService } from '../src/services/CafeApiService.js'
import { ApiError, HttpClient } from '../src/services/HttpClient.js'
import { MetodePembayaranStrategy } from '../src/services/pembayaran/MetodePembayaranStrategy.js'
import { PembayaranFactory } from '../src/services/pembayaran/PembayaranFactory.js'
import { PembayaranKasirStrategy } from '../src/services/pembayaran/PembayaranKasirStrategy.js'
import { PembayaranOnlineStrategy } from '../src/services/pembayaran/PembayaranOnlineStrategy.js'

test('HttpClient menormalisasi trailing slash URL produksi /api/v1/', () => {
  const client = new HttpClient('https://project-pbo.maulzzserver.my.id/api/v1/')
  assert.equal(client.baseUrl, 'https://project-pbo.maulzzserver.my.id/api/v1')
  assert.equal(
    client.buildUrl('/meja/QR-MEJA-01'),
    'https://project-pbo.maulzzserver.my.id/api/v1/meja/QR-MEJA-01',
  )
  assert.equal(
    client.buildUrl('/menus', { kategori_id: 2, search: 'kopi' }),
    'https://project-pbo.maulzzserver.my.id/api/v1/menus?kategori_id=2&search=kopi',
  )
})

test('CafeApiService memanggil endpoint backend yang benar dan memetakan response ke Model OOP', async () => {
  const calls = []
  const originalFetch = globalThis.fetch

  globalThis.fetch = async (url, init) => {
    calls.push({ url, method: init?.method ?? 'GET' })

    if (url.endsWith('/meja/QR-MEJA-01')) {
      return new Response(
        JSON.stringify({
          data: { id: 1, nomor_meja: 'M01', qr_code: 'QR-MEJA-01', status: 'tersedia', kapasitas: 2 },
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      )
    }

    if (url.endsWith('/pesanans')) {
      return new Response(
        JSON.stringify({
          message: 'Validasi gagal',
          errors: { 'items.0.menu_id': ['Menu Es Kopi Susu sedang habis atau stok tidak mencukupi.'] },
        }),
        { status: 422, headers: { 'Content-Type': 'application/json' } },
      )
    }

    return new Response(JSON.stringify({ message: 'Not found' }), { status: 404 })
  }

  try {
    const api = new CafeApiService(new HttpClient('https://project-pbo.maulzzserver.my.id/api/v1/'))
    const meja = await api.getMeja('QR-MEJA-01')

    assert.ok(meja instanceof Meja)
    assert.equal(meja.nomorMeja, 'M01')
    assert.equal(calls[0].url, 'https://project-pbo.maulzzserver.my.id/api/v1/meja/QR-MEJA-01')

    await assert.rejects(
      () => api.createPesanan({ meja_id: 1, nama_pengunjung: 'Budi', metode_pembayaran: 'kasir', items: [] }),
      (err) => {
        assert.ok(err instanceof ApiError)
        assert.equal(err.status, 422)
        assert.equal(err.getFirstValidationError(), 'Menu Es Kopi Susu sedang habis atau stok tidak mencukupi.')
        return true
      },
    )
  } finally {
    globalThis.fetch = originalFetch
  }
})

test('MetodePembayaranStrategy bersifat abstrak dan PembayaranFactory menghasilkan strategi polimorfik', async () => {
  assert.throws(() => new MetodePembayaranStrategy(), TypeError)

  let cekDipanggil = null
  const mockApi = {
    async cekPembayaran(nomor) {
      cekDipanggil = nomor
      return Pesanan.fromApi({
        id: 99,
        nomor_pesanan: nomor,
        status_pesanan: 'Pesanan Masuk',
        total_harga: 20000,
        pembayaran: {
          metode_pembayaran: 'online',
          status_pembayaran: 'lunas',
          transaksi_ref: 'MID-TRX-123',
        },
      })
    },
  }

  const kasir = PembayaranFactory.create('kasir', mockApi)
  const online = PembayaranFactory.create('online', mockApi, async () => ({
    pay(token, callbacks) {
      assert.equal(token, 'snap-token-123')
      callbacks.onSuccess()
    },
  }))

  assert.ok(kasir instanceof PembayaranKasirStrategy)
  assert.ok(kasir instanceof MetodePembayaranStrategy)
  assert.ok(online instanceof PembayaranOnlineStrategy)
  assert.ok(online instanceof MetodePembayaranStrategy)

  const pesananKasir = Pesanan.fromApi({
    id: 1,
    nomor_pesanan: 'ORD-20261125-KASIR',
    pembayaran: { metode_pembayaran: 'kasir', status_pembayaran: 'menunggu' },
  })
  const hasilKasir = await kasir.prosesPembayaran(pesananKasir)
  assert.equal(hasilKasir.status, 'menunggu')

  // Simulasi environment browser minimal untuk menguji window.snap.pay pada PembayaranOnlineStrategy
  const originalWindow = globalThis.window
  globalThis.window = {
    snap: {
      pay(token, callbacks) {
        assert.equal(token, 'snap-token-123')
        callbacks.onSuccess()
      },
    },
  }

  try {
    const pesananOnline = Pesanan.fromApi({
      id: 2,
      nomor_pesanan: 'ORD-20261125-ONL01',
      pembayaran: {
        metode_pembayaran: 'online',
        status_pembayaran: 'menunggu',
        snap_token: 'snap-token-123',
        snap_url: 'https://app.sandbox.midtrans.com/snap/v4/redirection/snap-token-123',
        client_key: 'SB-Mid-client-TEST',
      },
    })

    const hasilOnline = await online.prosesPembayaran(pesananOnline)
    assert.equal(cekDipanggil, 'ORD-20261125-ONL01')
    assert.equal(hasilOnline.status, 'lunas')
    assert.equal(hasilOnline.pesanan.pembayaran.isLunas(), true)
  } finally {
    globalThis.window = originalWindow
  }
})
