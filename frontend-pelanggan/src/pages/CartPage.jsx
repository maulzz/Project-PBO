import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useCart } from '../context/useCart.js'
import { formatRupiah } from '../lib/format.js'
import { cafeApiService } from '../services/CafeApiService.js'
import { ApiError } from '../services/HttpClient.js'
import { PembayaranFactory } from '../services/pembayaran/PembayaranFactory.js'

const METODE_OPTIONS = [
  ['kasir', 'Bayar di Kasir', 'Bayar tunai langsung di kasir dengan menyebutkan nomor pesanan'],
  ['online', 'Online (QRIS / e-Wallet)', 'Bayar praktis via Payment Gateway (Midtrans)'],
]

// KF-04, KF-05, KF-06, KF-07, KF-08, KF-10:
// Pengelolaan keranjang, catatan per item, wajib isi nama pengunjung, pilihan pembayaran online/kasir.
export default function CartPage() {
  const nav = useNavigate()
  const {
    cart,
    meja,
    lines,
    totalHarga,
    ubahJumlah,
    ubahCatatan,
    hapus,
    kosongkan,
    namaPengunjung,
    setNamaPengunjung,
    catatPesanan,
  } = useCart()

  const [nama, setNama] = useState(namaPengunjung || '')
  const [catatanPesanan, setCatatanPesanan] = useState('')
  const [metode, setMetode] = useState('kasir')
  const [kirim, setKirim] = useState(false)
  const [error, setError] = useState('')
  const [editCatatanKey, setEditCatatanKey] = useState(null)
  const [draftCatatanItem, setDraftCatatanItem] = useState('')

  const orderPath = meja?.qrCode ? `/order?meja=${encodeURIComponent(meja.qrCode)}` : '/order'

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
          to={orderPath}
          className="mt-4 inline-block rounded-lg bg-orange-600 px-5 py-2 font-semibold text-white"
        >
          Lihat Menu
        </Link>
      </div>
    )
  }

  function mulaiEditCatatan(line) {
    setEditCatatanKey(line.key)
    setDraftCatatanItem(line.catatan || '')
  }

  function simpanCatatanItem(key) {
    ubahCatatan(key, draftCatatanItem)
    setEditCatatanKey(null)
    setDraftCatatanItem('')
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
      setNamaPengunjung(nama.trim())
      const pesanan = await cafeApiService.createPesanan(payload)
      catatPesanan(pesanan)
      kosongkan()

      const strategy = PembayaranFactory.create(metode, cafeApiService)
      const hasil = await strategy.prosesPembayaran(pesanan)

      if (hasil.pesanan) {
        catatPesanan(hasil.pesanan)
      }

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
      <header className="flex items-center gap-3 bg-white px-4 py-4 shadow-2xs">
        <Link to={orderPath} className="text-xl" aria-label="Kembali">
          ←
        </Link>
        <h1 className="text-xl font-bold">Keranjang Pesanan</h1>
        <span className="ml-auto rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">
          {meja.getLabelMeja()}
        </span>
      </header>

      <ul className="space-y-3 p-4">
        {lines.map((l) => (
          <li key={l.key} className="rounded-xl bg-white p-3 shadow-sm">
            <div className="flex justify-between gap-2">
              <div>
                <p className="font-semibold">{l.menu.namaMenu}</p>
                <p className="text-xs text-stone-400">
                  {formatRupiah(l.getHargaSatuan())} / porsi
                </p>
              </div>
              <p className="font-semibold text-orange-700">{formatRupiah(l.getSubtotal())}</p>
            </div>

            {l.condiments.length > 0 && (
              <p className="mt-1 text-sm text-stone-600">
                +{' '}
                {l.condiments
                  .map((c) => `${c.namaCondiment} (${c.getFormattedHarga()})`)
                  .join(', ')}
              </p>
            )}

            {editCatatanKey === l.key ? (
              <div className="mt-2 space-y-1.5 rounded-lg bg-stone-50 p-2">
                <input
                  type="text"
                  value={draftCatatanItem}
                  onChange={(e) => setDraftCatatanItem(e.target.value)}
                  maxLength={500}
                  placeholder="Tulis catatan khusus menu ini…"
                  className="w-full rounded border border-stone-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-orange-500"
                />
                <div className="flex justify-end gap-2">
                  <button
                    type="button"
                    onClick={() => setEditCatatanKey(null)}
                    className="text-xs text-stone-500"
                  >
                    Batal
                  </button>
                  <button
                    type="button"
                    onClick={() => simpanCatatanItem(l.key)}
                    className="rounded bg-orange-600 px-2.5 py-1 text-xs font-semibold text-white"
                  >
                    Simpan Catatan
                  </button>
                </div>
              </div>
            ) : (
              <div className="mt-1 flex items-center justify-between gap-2">
                {l.catatan ? (
                  <p className="text-sm italic text-stone-500">“{l.catatan}”</p>
                ) : (
                  <span className="text-xs text-stone-400">Belum ada catatan menu</span>
                )}
                <button
                  type="button"
                  onClick={() => mulaiEditCatatan(l)}
                  className="text-xs font-medium text-orange-700 hover:underline"
                >
                  {l.catatan ? 'Ubah catatan' : '+ Catatan'}
                </button>
              </div>
            )}

            <div className="mt-3 flex items-center justify-between border-t border-stone-100 pt-2">
              <div className="flex items-center rounded-lg border border-stone-200">
                <button
                  type="button"
                  onClick={() => ubahJumlah(l.key, l.jumlah - 1)}
                  className="px-3 py-1"
                  aria-label="Kurangi jumlah"
                >
                  −
                </button>
                <span className="w-8 text-center text-sm font-semibold">{l.jumlah}</span>
                <button
                  type="button"
                  onClick={() => ubahJumlah(l.key, l.jumlah + 1)}
                  className="px-3 py-1"
                  aria-label="Tambah jumlah"
                >
                  +
                </button>
              </div>
              <button
                type="button"
                onClick={() => hapus(l.key)}
                className="text-sm font-medium text-red-600 hover:underline"
              >
                Hapus
              </button>
            </div>
          </li>
        ))}
      </ul>

      <section className="space-y-4 px-4">
        <label className="block text-sm font-semibold">
          Nama pengunjung <span className="text-red-600">*</span>
          <input
            value={nama}
            onChange={(e) => {
              setNama(e.target.value)
              setNamaPengunjung(e.target.value)
            }}
            maxLength={255}
            required
            placeholder="Wajib diisi untuk identifikasi pesanan (contoh: Andi)"
            className="mt-1 w-full rounded-lg border border-stone-200 bg-white px-3 py-2 font-normal outline-none focus:border-orange-500"
          />
        </label>

        <label className="block text-sm font-semibold">
          Catatan pesanan tambahan (opsional)
          <textarea
            value={catatanPesanan}
            onChange={(e) => setCatatanPesanan(e.target.value)}
            maxLength={1000}
            rows={2}
            placeholder="Contoh: Antar agak cepat ya"
            className="mt-1 w-full rounded-lg border border-stone-200 bg-white px-3 py-2 text-sm font-normal outline-none focus:border-orange-500"
          />
        </label>

        <fieldset>
          <legend className="text-sm font-semibold">Metode pembayaran</legend>
          <div className="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
            {METODE_OPTIONS.map(([nilai, label, keterangan]) => (
              <label
                key={nilai}
                className={`cursor-pointer rounded-xl border-2 p-3 text-left transition ${
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
                <p className="text-sm font-semibold text-stone-900">{label}</p>
                <p className="mt-0.5 text-xs text-stone-500">{keterangan}</p>
              </label>
            ))}
          </div>
        </fieldset>

        {error && (
          <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm font-medium text-red-700">
            {error}
          </p>
        )}

        <div className="flex items-center justify-between border-t border-stone-200 pt-4">
          <span className="text-stone-500">Total Pembayaran</span>
          <span className="text-xl font-bold text-orange-700">{formatRupiah(totalHarga)}</span>
        </div>

        <button
          type="submit"
          disabled={kirim}
          className="w-full rounded-xl bg-orange-600 py-3.5 font-semibold text-white shadow-sm hover:bg-orange-700 disabled:opacity-60"
        >
          {kirim ? 'Memproses Pesanan…' : 'Kirim Pesanan'}
        </button>
      </section>
    </form>
  )
}
