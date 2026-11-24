import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { createPesanan } from '../lib/api'
import { formatRupiah } from '../lib/format'
import { useCart } from '../context/CartContext'


export default function CartPage() {
  const nav = useNavigate()
  const { meja, lines, totalHarga, hargaSatuan, ubahJumlah, hapus, kosongkan } = useCart()

  const [nama, setNama] = useState('')
  const [metode, setMetode] = useState('kasir') 
  const [kirim, setKirim] = useState(false)
  const [error, setError] = useState('')

  if (lines.length === 0)
    return (
      <div className="p-8 text-center">
        <p className="text-4xl">🛒</p>
        <p className="mt-3 font-semibold">Keranjang masih kosong</p>
        <Link to="/order" className="mt-4 inline-block rounded-lg bg-orange-600 px-5 py-2 font-semibold text-white">
          Lihat Menu
        </Link>
      </div>
    )

  async function submit(e) {
    e.preventDefault()
    setError('')
    if (!nama.trim()) return setError('Nama pengunjung wajib diisi.')

    setKirim(true)
    try {
      const pesanan = await createPesanan({
        meja_id: meja.id,
        nama_pengunjung: nama.trim(),
        metode_pembayaran: metode,
        items: lines.map((l) => ({
          menu_id: l.menu.id,
          jumlah: l.jumlah,
          catatan: l.catatan || null,
          condiment_ids: l.condiments.map((c) => c.id),
        })),
      })
      kosongkan()

      if (metode === 'online' && pesanan.pembayaran?.snap_url) {
        window.location.href = pesanan.pembayaran.snap_url
        return
      }
      nav(`/pesanan/${pesanan.nomor_pesanan}`, { replace: true })
    } catch (err) {
      const pertama = err.errors ? Object.values(err.errors)[0]?.[0] : null
      setError(pertama ?? err.message)
      setKirim(false)
    }
  }

  return (
    <form onSubmit={submit} className="pb-8">
      <header className="flex items-center gap-3 bg-white px-4 py-4">
        <Link to="/order" className="text-xl" aria-label="Kembali">←</Link>
        <h1 className="text-xl font-bold">Keranjang</h1>
        <span className="ml-auto text-sm text-stone-500">Meja {meja?.nomor_meja}</span>
      </header>

      <ul className="space-y-3 p-4">
        {lines.map((l) => (
          <li key={l.key} className="rounded-xl bg-white p-3 shadow-sm">
            <div className="flex justify-between gap-2">
              <p className="font-semibold">{l.menu.nama_menu}</p>
              <p className="font-semibold">{formatRupiah(hargaSatuan(l) * l.jumlah)}</p>
            </div>
            {l.condiments.length > 0 && (
              <p className="text-sm text-stone-500">+ {l.condiments.map((c) => c.nama_condiment).join(', ')}</p>
            )}
            {l.catatan && <p className="text-sm italic text-stone-500">“{l.catatan}”</p>}
            <div className="mt-2 flex items-center justify-between">
              <div className="flex items-center rounded-lg border border-stone-200">
                <button type="button" onClick={() => ubahJumlah(l.key, l.jumlah - 1)} className="px-3 py-1">−</button>
                <span className="w-8 text-center">{l.jumlah}</span>
                <button type="button" onClick={() => ubahJumlah(l.key, l.jumlah + 1)} className="px-3 py-1">+</button>
              </div>
              <button type="button" onClick={() => hapus(l.key)} className="text-sm text-red-600">Hapus</button>
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

        <fieldset>
          <legend className="text-sm font-semibold">Metode pembayaran</legend>
          <div className="mt-2 grid grid-cols-2 gap-2">
            {[
              ['kasir', 'Bayar di Kasir'],
              ['online', 'Online (QRIS/e-wallet)'],
            ].map(([nilai, label]) => (
              <label
                key={nilai}
                className={`cursor-pointer rounded-lg border-2 p-3 text-center text-sm font-medium ${
                  metode === nilai ? 'border-orange-600 bg-orange-50' : 'border-stone-200 bg-white'
                }`}
              >
                <input type="radio" name="metode" value={nilai} checked={metode === nilai}
                  onChange={() => setMetode(nilai)} className="sr-only" />
                {label}
              </label>
            ))}
          </div>
        </fieldset>

        {error && <p role="alert" className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p>}

        <div className="flex items-center justify-between border-t border-stone-200 pt-4">
          <span className="text-stone-500">Total</span>
          <span className="text-xl font-bold">{formatRupiah(totalHarga)}</span>
        </div>

        <button
          type="submit"
          disabled={kirim}
          className="w-full rounded-lg bg-orange-600 py-3 font-semibold text-white disabled:opacity-60"
        >
          {kirim ? 'Mengirim…' : 'Kirim Pesanan'}
        </button>
      </section>
    </form>
  )
}
