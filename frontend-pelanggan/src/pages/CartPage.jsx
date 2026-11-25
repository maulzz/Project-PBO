import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useCart } from '../context/useCart.js'
import { formatRupiah } from '../lib/format.js'
import { cafeApiService } from '../services/CafeApiService.js'
import { ApiError } from '../services/HttpClient.js'
import { PembayaranFactory } from '../services/pembayaran/PembayaranFactory.js'

const METODE_OPTIONS = [
  ['kasir', 'Bayar di Kasir'],
  ['online', 'Online (QRIS / e-Wallet)'],
]

export default function CartPage() {
  const nav = useNavigate()
  const { cart, meja, lines, totalHarga, ubahJumlah, hapus, kosongkan } = useCart()

  const [nama, setNama] = useState('')
  const [catatanPesanan, setCatatanPesanan] = useState('')
  const [metode, setMetode] = useState('kasir')
  const [kirim, setKirim] = useState(false)
  const [error, setError] = useState('')

  if (!meja) {
    return (
      <div className="p-8 text-center">
        <p className="text-4xl">📷</p>
        <p className="mt-3 font-semibold">Meja belum teridentifikasi.</p>
        <p className="mt-1 text-sm text-stone-500">
          Silakan scan QR Code di meja Anda terlebih dahulu.
        </p>
        <Link
          to="/order"
          className="mt-4 inline-block rounded-lg bg-orange-600 px-5 py-2 font-semibold text-white"
        >
          Kembali ke Menu
        </Link>
      </div>
    )
  }

  if (lines.length === 0) {
    return (
      <div className="p-8 text-center">
        <p className="text-4xl">🛒</p>
        <p className="mt-3 font-semibold">Keranjang masih kosong</p>
        <Link
          to="/order"
          className="mt-4 inline-block rounded-lg bg-orange-600 px-5 py-2 font-semibold text-white"
        >
          Lihat Menu
        </Link>
      </div>
    )
  }

  async function submit(e) {
    e.preventDefault()
    setError('')

    let payload
    try {
      payload = cart.toCheckoutPayload({
        namaPengunjung: nama,
        metodePembayaran: metode,
        catatan: catatanPesanan,
      })
    } catch (validationErr) {
      setError(validationErr.message)
      return
    }

    setKirim(true)
    try {
      const pesanan = await cafeApiService.createPesanan(payload)
      kosongkan()

      const strategy = PembayaranFactory.create(metode, cafeApiService)
      const hasil = await strategy.prosesPembayaran(pesanan)

      if (hasil.status !== 'redirected') {
        nav(`/pesanan/${encodeURIComponent(pesanan.nomorPesanan)}`, { replace: true })
      }
    } catch (err) {
      const pesanValidasi = err instanceof ApiError ? err.getFirstValidationError() : null
      setError(pesanValidasi ?? err.message)
      setKirim(false)
    }
  }

  return (
    <form onSubmit={submit} className="pb-8">
      <header className="flex items-center gap-3 bg-white px-4 py-4">
        <Link to="/order" className="text-xl" aria-label="Kembali">
          ←
        </Link>
        <h1 className="text-xl font-bold">Keranjang</h1>
        <span className="ml-auto text-sm text-stone-500">{meja.getLabelMeja()}</span>
      </header>

      <ul className="space-y-3 p-4">
        {lines.map((l) => (
          <li key={l.key} className="rounded-xl bg-white p-3 shadow-sm">
            <div className="flex justify-between gap-2">
              <p className="font-semibold">{l.menu.namaMenu}</p>
              <p className="font-semibold">{formatRupiah(l.getSubtotal())}</p>
            </div>
            {l.condiments.length > 0 && (
              <p className="text-sm text-stone-500">
                + {l.condiments.map((c) => c.namaCondiment).join(', ')}
              </p>
            )}
            {l.catatan && <p className="text-sm italic text-stone-500">“{l.catatan}”</p>}
            <div className="mt-2 flex items-center justify-between">
              <div className="flex items-center rounded-lg border border-stone-200">
                <button
                  type="button"
                  onClick={() => ubahJumlah(l.key, l.jumlah - 1)}
                  className="px-3 py-1"
                >
                  −
                </button>
                <span className="w-8 text-center">{l.jumlah}</span>
                <button
                  type="button"
                  onClick={() => ubahJumlah(l.key, l.jumlah + 1)}
                  className="px-3 py-1"
                >
                  +
                </button>
              </div>
              <button
                type="button"
                onClick={() => hapus(l.key)}
                className="text-sm text-red-600"
              >
                Hapus
              </button>
            </div>
          </li>
        ))}
      </ul>

      <section className="space-y-4 px-4">
        <label className="block text-sm font-semibold">
          Nama pemesan <span className="text-red-600">*</span>
          <input
            value={nama}
            onChange={(e) => setNama(e.target.value)}
            maxLength={255}
            placeholder="Contoh: Andi"
            className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 font-normal outline-none focus:border-orange-500"
          />
        </label>

        <label className="block text-sm font-semibold">
          Catatan pesanan (opsional)
          <textarea
            value={catatanPesanan}
            onChange={(e) => setCatatanPesanan(e.target.value)}
            maxLength={1000}
            rows={2}
            placeholder="Contoh: Antar agak cepat ya"
            className="mt-1 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm font-normal outline-none focus:border-orange-500"
          />
        </label>

        <fieldset>
          <legend className="text-sm font-semibold">Metode pembayaran</legend>
          <div className="mt-2 grid grid-cols-2 gap-2">
            {METODE_OPTIONS.map(([nilai, label]) => (
              <label
                key={nilai}
                className={`cursor-pointer rounded-lg border-2 p-3 text-center text-sm font-medium ${
                  metode === nilai ? 'border-orange-600 bg-orange-50' : 'border-stone-200 bg-white'
                }`}
              >
                <input
                  type="radio"
                  name="metode"
                  value={nilai}
                  checked={metode === nilai}
                  onChange={() => setMetode(nilai)}
                  className="sr-only"
                />
                {label}
              </label>
            ))}
          </div>
        </fieldset>

        {error && (
          <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">
            {error}
          </p>
        )}

        <div className="flex items-center justify-between border-t border-stone-200 pt-4">
          <span className="text-stone-500">Total</span>
          <span className="text-xl font-bold">{formatRupiah(totalHarga)}</span>
        </div>

        <button
          type="submit"
          disabled={kirim}
          className="w-full rounded-lg bg-orange-600 py-3 font-semibold text-white disabled:opacity-60"
        >
          {kirim ? 'Memproses Pesanan…' : 'Kirim Pesanan'}
        </button>
      </section>
    </form>
  )
}
