import { useEffect, useMemo, useState } from 'react'
import { Link, useSearchParams } from 'react-router-dom'
import { getKategoris, getMenus, getMeja } from '../lib/api'
import { formatRupiah } from '../lib/format'
import { useCart } from '../context/CartContext'
import ItemSheet from '../components/ItemSheet'

// KF-01, KF-02, KF-03, KF-05, KF-09: scan QR -> mencari meja -> daftar menu.
export default function OrderPage() {
  const [params] = useSearchParams()
  const qr = params.get('meja')
  const { meja, setMeja, tambah, totalItem, totalHarga } = useCart()

  const [kategoris, setKategoris] = useState([])
  const [menus, setMenus] = useState([])
  const [kategoriId, setKategoriId] = useState(null)
  const [cari, setCari] = useState('')
  const [dipilih, setDipilih] = useState(null) // menu yang sedang dibuka di sheet
  const [status, setStatus] = useState('loading') // loading | ok | error
  const [pesanError, setPesanError] = useState('')

  useEffect(() => {
    let batal = false
    async function muat() {
      try {
        // 1) kenali meja dari QR; kalau tidak ada di URL, pakai meja yang tersimpan
        if (qr) {
          const m = await getMeja(qr)
          if (!batal) setMeja(m)
        } else if (!meja) {
          throw new Error('Silakan scan QR Code di meja Anda untuk mulai memesan.')
        }
        // 2) muat kategori & menu secara paralel
        const [k, m] = await Promise.all([getKategoris(), getMenus()])
        if (batal) return
        setKategoris(k)
        setMenus(m)
        setStatus('ok')
      } catch (e) {
        if (batal) return
        setPesanError(e.message)
        setStatus('error')
      }
    }
    muat()
    return () => { batal = true }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qr])

  const tampil = useMemo(
    () =>
      menus.filter(
        (m) =>
          (!kategoriId || m.kategori?.id === kategoriId) &&
          m.nama_menu.toLowerCase().includes(cari.trim().toLowerCase()),
      ),
    [menus, kategoriId, cari],
  )

  if (status === 'loading') return <p className="p-8 text-center text-stone-500">Memuat menu…</p>

  if (status === 'error')
    return (
      <div className="p-8 text-center">
        <p className="text-4xl">📷</p>
        <p className="mt-3 font-semibold">{pesanError}</p>
      </div>
    )

  return (
    <div className="pb-28">
      <header className="sticky top-0 z-10 bg-white/95 px-4 pb-3 pt-4 backdrop-blur">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-bold">Menu</h1>
          <span className="rounded-full bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-700">
            Meja {meja?.nomor_meja}
          </span>
        </div>
        <input
          value={cari}
          onChange={(e) => setCari(e.target.value)}
          placeholder="Cari menu…"
          className="mt-3 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm outline-none focus:border-orange-500"
        />
        <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
          {[{ id: null, nama_kategori: 'Semua' }, ...kategoris].map((k) => (
            <button
              key={k.id ?? 'semua'}
              onClick={() => setKategoriId(k.id)}
              className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-medium ${
                kategoriId === k.id ? 'bg-orange-600 text-white' : 'bg-stone-100 text-stone-700'
              }`}
            >
              {k.nama_kategori}
            </button>
          ))}
        </div>
      </header>

      <ul className="space-y-3 px-4 pt-3">
        {tampil.length === 0 && <li className="py-10 text-center text-stone-500">Menu tidak ditemukan.</li>}
        {tampil.map((m) => (
          <li key={m.id}>
            <button
              disabled={!m.bisa_dipesan}
              onClick={() => setDipilih(m)}
              className="flex w-full gap-3 rounded-xl bg-white p-3 text-left shadow-sm disabled:opacity-60"
            >
              {m.gambar_url ? (
                <img src={m.gambar_url} alt="" className="size-20 shrink-0 rounded-lg object-cover" />
              ) : (
                <div className="grid size-20 shrink-0 place-items-center rounded-lg bg-stone-100 text-2xl">🍽️</div>
              )}
              <div className="min-w-0 flex-1">
                <p className="font-semibold">{m.nama_menu}</p>
                <p className="line-clamp-2 text-sm text-stone-500">{m.deskripsi}</p>
                <div className="mt-1 flex items-center justify-between">
                  <span className="font-semibold text-orange-700">{formatRupiah(m.harga)}</span>
                  {m.stok_habis && (
                    <span className="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                      {m.status_teks}
                    </span>
                  )}
                </div>
              </div>
            </button>
          </li>
        ))}
      </ul>

      {totalItem > 0 && (
        <Link
          to="/keranjang"
          className="fixed bottom-4 left-1/2 flex w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 items-center justify-between rounded-xl bg-orange-600 px-5 py-3 font-semibold text-white shadow-lg"
        >
          <span>🛒 {totalItem} item</span>
          <span>{formatRupiah(totalHarga)}</span>
        </Link>
      )}

      {dipilih && (
        <ItemSheet
          menu={dipilih}
          onClose={() => setDipilih(null)}
          onAdd={(item) => { tambah(item); setDipilih(null) }}
        />
      )}
    </div>
  )
}
