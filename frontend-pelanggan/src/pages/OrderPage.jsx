import { useEffect, useMemo, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import ItemSheet from '../components/ItemSheet.jsx'
import { useCart } from '../context/useCart.js'
import { formatRupiah } from '../lib/format.js'
import { cafeApiService } from '../services/CafeApiService.js'

// KF-01, KF-02, KF-03, KF-05, KF-08, KF-09, Batasan 4.3.1:
// Scan QR -> identifikasi nomor meja -> daftar menu (nama, deskripsi, kategori, harga, status stok tanpa angka stok).
export default function OrderPage() {
  const nav = useNavigate()
  const [params] = useSearchParams()
  const routeParams = useParams()
  const qr = (params.get('meja') || params.get('qr') || routeParams.qrCode || '').trim()

  const {
    meja,
    setMeja,
    clearMeja,
    tambah,
    totalItem,
    totalHarga,
    riwayatPesanan,
  } = useCart()

  const [kategoris, setKategoris] = useState([])
  const [menus, setMenus] = useState([])
  const [kategoriId, setKategoriId] = useState(null)
  const [cari, setCari] = useState('')
  const [dipilih, setDipilih] = useState(null)
  const [status, setStatus] = useState('loading') // loading | ok | error
  const [pesanError, setPesanError] = useState('')
  const [inputNomorPesanan, setInputNomorPesanan] = useState('')
  const [tampilLacak, setTampilLacak] = useState(false)
  const [brokenImages, setBrokenImages] = useState({})

  const hasMejaTersimpan = Boolean(meja?.id)

  useEffect(() => {
    let batal = false

    async function muat() {
      setStatus('loading')
      setPesanError('')

      try {
        if (qr) {
          try {
            const mejaData = await cafeApiService.getMeja(qr)
            if (!batal) setMeja(mejaData)
          } catch (mejaErr) {
            if (!batal) clearMeja()
            throw mejaErr
          }
        } else if (!hasMejaTersimpan) {
          throw new Error('Silakan scan QR Code yang tersedia pada meja Anda untuk mulai memesan.')
        }

        const [kategoriList, menuList] = await Promise.all([
          cafeApiService.getKategoris(),
          cafeApiService.getMenus(),
        ])

        if (batal) return
        setKategoris(kategoriList)
        setMenus(menuList)
        setStatus('ok')
      } catch (err) {
        if (batal) return
        setPesanError(err.message)
        setStatus('error')
      }
    }

    muat()
    return () => {
      batal = true
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [qr, hasMejaTersimpan])

  const tampil = useMemo(
    () => menus.filter((m) => m.matchesFilter(kategoriId, cari)),
    [menus, kategoriId, cari],
  )

  const kategoriAktif = useMemo(
    () => kategoris.find((k) => k.id === kategoriId) ?? null,
    [kategoris, kategoriId],
  )

  const pesananMejaIni = useMemo(() => {
    if (!riwayatPesanan?.length) return []
    if (!meja?.nomorMeja) return riwayatPesanan.slice(0, 3)
    const cocokMeja = riwayatPesanan.filter(
      (p) => !p.nomorMeja || p.nomorMeja === '-' || p.nomorMeja === meja.nomorMeja,
    )
    return (cocokMeja.length > 0 ? cocokMeja : riwayatPesanan).slice(0, 3)
  }, [riwayatPesanan, meja])

  function handleLacakSubmit(e) {
    e.preventDefault()
    const nomor = inputNomorPesanan.trim().toUpperCase()
    if (!nomor) return
    nav(`/pesanan/${encodeURIComponent(nomor)}`)
  }

  if (status === 'loading') {
    return <p className="p-8 text-center text-stone-500">Memuat menu…</p>
  }

  if (status === 'error') {
    return (
      <div className="p-8 text-center">
        <p className="text-4xl">📷</p>
        <p className="mt-3 font-semibold text-stone-800">{pesanError}</p>
        <p className="mt-1 text-xs text-stone-500">
          Sesuai aturan pemesanan cafe, pelanggan hanya dapat memesan setelah memindai QR Code pada
          meja.
        </p>

        <div className="mt-6 rounded-xl bg-white p-4 text-left shadow-sm">
          <p className="text-xs font-semibold text-stone-600">Sudah memesan sebelumnya?</p>
          <form onSubmit={handleLacakSubmit} className="mt-2 flex gap-2">
            <input
              value={inputNomorPesanan}
              onChange={(e) => setInputNomorPesanan(e.target.value)}
              placeholder="Masukkan Nomor Pesanan (ORD-...)"
              className="flex-1 rounded-lg border border-stone-200 px-3 py-2 text-xs outline-none focus:border-orange-500"
            />
            <button
              type="submit"
              className="rounded-lg bg-orange-600 px-3 py-2 text-xs font-semibold text-white"
            >
              Cek Status
            </button>
          </form>
        </div>
      </div>
    )
  }

  return (
    <div className="pb-28">
      <header className="sticky top-0 z-10 bg-white/95 px-4 pb-3 pt-4 shadow-xs backdrop-blur">
        <div className="flex items-center justify-between gap-2">
          <div>
            <h1 className="text-xl font-bold">Daftar Menu</h1>
            {meja?.kapasitas && (
              <p className="text-xs text-stone-500">Kapasitas {meja.kapasitas} orang</p>
            )}
          </div>
          <div className="flex items-center gap-2">
            <button
              type="button"
              onClick={() => setTampilLacak((v) => !v)}
              className="rounded-full border border-stone-200 bg-stone-50 px-3 py-1 text-xs font-medium text-stone-700 hover:border-orange-400"
            >
              Cek Pesanan
            </button>
            <span className="rounded-full bg-orange-100 px-3 py-1 text-sm font-semibold text-orange-700">
              {meja?.getLabelMeja()}
            </span>
          </div>
        </div>

        {tampilLacak && (
          <form
            onSubmit={handleLacakSubmit}
            className="mt-3 flex gap-2 rounded-xl border border-orange-200 bg-orange-50/60 p-2.5"
          >
            <input
              value={inputNomorPesanan}
              onChange={(e) => setInputNomorPesanan(e.target.value)}
              placeholder="Nomor pesanan (contoh: ORD-20261125-ABCDE)"
              className="flex-1 rounded-lg border border-stone-200 bg-white px-3 py-1.5 text-xs outline-none focus:border-orange-500"
            />
            <button
              type="submit"
              className="rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white"
            >
              Lacak
            </button>
          </form>
        )}

        <input
          value={cari}
          onChange={(e) => setCari(e.target.value)}
          placeholder="Cari nama menu, kategori, atau deskripsi…"
          className="mt-3 w-full rounded-lg border border-stone-200 px-3 py-2 text-sm outline-none focus:border-orange-500"
        />

        <div className="mt-3 flex gap-2 overflow-x-auto pb-1">
          {[{ id: null, namaKategori: 'Semua' }, ...kategoris].map((k) => (
            <button
              key={k.id ?? 'semua'}
              type="button"
              onClick={() => setKategoriId(k.id)}
              className={`shrink-0 rounded-full px-4 py-1.5 text-sm font-medium transition ${
                kategoriId === k.id ? 'bg-orange-600 text-white' : 'bg-stone-100 text-stone-700'
              }`}
            >
              {k.namaKategori}
            </button>
          ))}
        </div>

        {kategoriAktif?.deskripsi && (
          <p className="mt-1.5 text-xs text-stone-500">{kategoriAktif.deskripsi}</p>
        )}
      </header>

      {pesananMejaIni.length > 0 && (
        <section className="mx-4 mt-3 rounded-xl border border-orange-200 bg-orange-50/70 p-3">
          <div className="flex items-center justify-between">
            <p className="text-xs font-semibold text-orange-900">Pesanan Terakhir Anda</p>
            <span className="text-[11px] text-orange-700">Ketuk untuk melihat status</span>
          </div>
          <ul className="mt-2 space-y-1.5">
            {pesananMejaIni.map((p) => (
              <li key={p.nomorPesanan}>
                <Link
                  to={`/pesanan/${encodeURIComponent(p.nomorPesanan)}`}
                  className="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-xs shadow-2xs hover:bg-orange-50"
                >
                  <div>
                    <span className="font-bold text-stone-800">{p.nomorPesanan}</span>
                    {p.namaPengunjung && (
                      <span className="ml-1.5 text-stone-500">({p.namaPengunjung})</span>
                    )}
                  </div>
                  <div className="flex items-center gap-1.5">
                    <span
                      className={`rounded-full px-2 py-0.5 text-[11px] font-semibold ${p.getStatusBadgeClass()}`}
                    >
                      {p.statusPesanan}
                    </span>
                    <span className="text-orange-600">→</span>
                  </div>
                </Link>
              </li>
            ))}
          </ul>
        </section>
      )}

      <ul className="space-y-3 px-4 pt-3">
        {tampil.length === 0 && (
          <li className="py-10 text-center text-stone-500">Menu tidak ditemukan.</li>
        )}
        {tampil.map((m) => {
          const tersedia = m.isTersedia()
          const showImg = m.gambarUrl && !brokenImages[m.id]

          return (
            <li key={m.id}>
              <button
                type="button"
                disabled={!tersedia}
                onClick={() => {
                  if (tersedia) setDipilih(m)
                }}
                className="flex w-full gap-3 rounded-xl bg-white p-3 text-left shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
              >
                {showImg ? (
                  <img
                    src={m.gambarUrl}
                    alt={m.namaMenu}
                    onError={() =>
                      setBrokenImages((prev) => ({
                        ...prev,
                        [m.id]: true,
                      }))
                    }
                    className="size-20 shrink-0 rounded-lg object-cover"
                  />
                ) : (
                  <div className="grid size-20 shrink-0 place-items-center rounded-lg bg-stone-100 text-2xl">
                    🍽️
                  </div>
                )}

                <div className="min-w-0 flex-1">
                  <div className="flex flex-wrap items-center justify-between gap-1">
                    {m.namaKategori && (
                      <span className="rounded bg-stone-100 px-2 py-0.5 text-[11px] font-medium text-stone-600">
                        {m.namaKategori}
                      </span>
                    )}
                    {!tersedia ? (
                      <span className="rounded bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                        {m.statusTeks || 'Stok Habis'}
                      </span>
                    ) : (
                      <span className="rounded bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                        {m.statusTeks || 'Tersedia'}
                      </span>
                    )}
                  </div>

                  <p className="mt-1 font-semibold text-stone-900">{m.namaMenu}</p>
                  {m.deskripsi && (
                    <p className="line-clamp-2 text-sm text-stone-500">{m.deskripsi}</p>
                  )}

                  <div className="mt-2 flex items-center justify-between">
                    <span className="font-semibold text-orange-700">{m.getFormattedHarga()}</span>
                    {m.hasCondiments() && tersedia && (
                      <span className="text-[11px] text-stone-400">
                        +{m.getAvailableCondiments().length} pilihan tambahan
                      </span>
                    )}
                  </div>
                </div>
              </button>
            </li>
          )
        })}
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
          onAdd={(item) => {
            tambah(item)
            setDipilih(null)
          }}
        />
      )}
    </div>
  )
}
