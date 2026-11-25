import { useState } from 'react'
import { formatRupiah } from '../lib/format.js'

// KF-03, KF-04, KF-05, KF-06, KF-09: Detail menu, pilihan condiment + harga tambahan, catatan khusus, & jumlah.
export default function ItemSheet({ menu, onClose, onAdd }) {
  const [jumlah, setJumlah] = useState(1)
  const [terpilih, setTerpilih] = useState([])
  const [catatan, setCatatan] = useState('')
  const [imgError, setImgError] = useState(false)

  const condiments = menu.getAvailableCondiments()
  const pilihan = condiments.filter((c) => terpilih.includes(c.id))
  const hargaSatuan = menu.harga + pilihan.reduce((n, c) => n + c.hargaCondiment, 0)
  const tersedia = menu.isTersedia()

  const toggle = (id) =>
    setTerpilih((cur) => (cur.includes(id) ? cur.filter((x) => x !== id) : [...cur, id]))

  return (
    <div className="fixed inset-0 z-30 flex items-end bg-black/40" onClick={onClose}>
      <div
        className="mx-auto max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-3">
          <div className="flex gap-3">
            {menu.gambarUrl && !imgError ? (
              <img
                src={menu.gambarUrl}
                alt={menu.namaMenu}
                onError={() => setImgError(true)}
                className="size-16 shrink-0 rounded-xl object-cover"
              />
            ) : (
              <div className="grid size-16 shrink-0 place-items-center rounded-xl bg-stone-100 text-2xl">
                🍽️
              </div>
            )}
            <div>
              <div className="flex flex-wrap items-center gap-1.5">
                {menu.namaKategori && (
                  <span className="rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-700">
                    {menu.namaKategori}
                  </span>
                )}
                <span
                  className={`rounded-full px-2 py-0.5 text-xs font-semibold ${
                    tersedia ? 'bg-emerald-50 text-emerald-700' : 'bg-red-100 text-red-700'
                  }`}
                >
                  {tersedia ? menu.statusTeks || 'Tersedia' : menu.statusTeks || 'Stok Habis'}
                </span>
              </div>
              <h2 className="mt-1 text-lg font-bold">{menu.namaMenu}</h2>
              <p className="text-sm font-semibold text-orange-700">{menu.getFormattedHarga()}</p>
              {menu.deskripsi && <p className="mt-1 text-sm text-stone-500">{menu.deskripsi}</p>}
            </div>
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Tutup"
            className="text-2xl leading-none text-stone-400 hover:text-stone-600"
          >
            ×
          </button>
        </div>

        {menu.hasCondiments() && (
          <fieldset className="mt-4">
            <legend className="text-sm font-semibold">
              Pilihan Tambahan / Condiment (opsional)
            </legend>
            <div className="mt-2 space-y-2">
              {condiments.map((c) => (
                <label
                  key={c.id}
                  className={`flex cursor-pointer items-center justify-between rounded-lg border px-3 py-2 transition ${
                    terpilih.includes(c.id)
                      ? 'border-orange-500 bg-orange-50/50'
                      : 'border-stone-200 bg-white'
                  }`}
                >
                  <span className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={terpilih.includes(c.id)}
                      onChange={() => toggle(c.id)}
                      className="size-4 accent-orange-600"
                    />
                    {c.namaCondiment}
                  </span>
                  <span className="text-sm font-medium text-stone-600">
                    {c.getFormattedHarga()}
                  </span>
                </label>
              ))}
            </div>
          </fieldset>
        )}

        <label className="mt-4 block text-sm font-semibold">
          Catatan / Permintaan Khusus (opsional)
          <textarea
            value={catatan}
            onChange={(e) => setCatatan(e.target.value)}
            maxLength={500}
            rows={2}
            placeholder="Contoh: tidak pakai es, gula sedikit"
            className="mt-1 w-full rounded-lg border border-stone-200 p-2 text-sm font-normal outline-none focus:border-orange-500"
          />
        </label>

        <div className="mt-5 flex items-center gap-3">
          <div className="flex items-center rounded-lg border border-stone-200">
            <button
              type="button"
              onClick={() => setJumlah((n) => Math.max(1, n - 1))}
              className="px-3 py-2 text-lg"
              aria-label="Kurangi jumlah"
            >
              −
            </button>
            <span className="w-8 text-center font-semibold">{jumlah}</span>
            <button
              type="button"
              onClick={() => setJumlah((n) => Math.min(99, n + 1))}
              className="px-3 py-2 text-lg"
              aria-label="Tambah jumlah"
            >
              +
            </button>
          </div>
          <button
            type="button"
            disabled={!tersedia}
            onClick={() => {
              if (!tersedia) return
              onAdd({ menu, jumlah, condiments: pilihan, catatan })
            }}
            className="flex-1 rounded-lg bg-orange-600 py-3 font-semibold text-white active:bg-orange-700 disabled:cursor-not-allowed disabled:bg-stone-300"
          >
            {tersedia
              ? `Tambah · ${formatRupiah(hargaSatuan * jumlah)}`
              : menu.statusTeks || 'Stok Habis'}
          </button>
        </div>
      </div>
    </div>
  )
}
