import { useState } from 'react'
import { formatRupiah } from '../lib/format.js'

export default function ItemSheet({ menu, onClose, onAdd }) {
  const [jumlah, setJumlah] = useState(1)
  const [terpilih, setTerpilih] = useState([])
  const [catatan, setCatatan] = useState('')

  const condiments = menu.condiments ?? []
  const pilihan = condiments.filter((c) => terpilih.includes(c.id))
  const hargaSatuan = menu.harga + pilihan.reduce((n, c) => n + c.hargaCondiment, 0)

  const toggle = (id) =>
    setTerpilih((cur) => (cur.includes(id) ? cur.filter((x) => x !== id) : [...cur, id]))

  return (
    <div className="fixed inset-0 z-30 flex items-end bg-black/40" onClick={onClose}>
      <div
        className="mx-auto max-h-[90vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="flex items-start justify-between gap-3">
          <div>
            <h2 className="text-lg font-bold">{menu.namaMenu}</h2>
            {menu.deskripsi && <p className="mt-1 text-sm text-stone-500">{menu.deskripsi}</p>}
          </div>
          <button
            type="button"
            onClick={onClose}
            aria-label="Tutup"
            className="text-2xl leading-none text-stone-400"
          >
            ×
          </button>
        </div>

        {menu.hasCondiments() && (
          <fieldset className="mt-4">
            <legend className="text-sm font-semibold">Tambahan (opsional)</legend>
            <div className="mt-2 space-y-2">
              {condiments.map((c) => (
                <label
                  key={c.id}
                  className="flex cursor-pointer items-center justify-between rounded-lg border border-stone-200 px-3 py-2"
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
                  <span className="text-sm text-stone-500">{c.getFormattedHarga()}</span>
                </label>
              ))}
            </div>
          </fieldset>
        )}

        <label className="mt-4 block text-sm font-semibold">
          Catatan (opsional)
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
            >
              −
            </button>
            <span className="w-8 text-center font-semibold">{jumlah}</span>
            <button
              type="button"
              onClick={() => setJumlah((n) => Math.min(99, n + 1))}
              className="px-3 py-2 text-lg"
            >
              +
            </button>
          </div>
          <button
            type="button"
            onClick={() => onAdd({ menu, jumlah, condiments: pilihan, catatan })}
            className="flex-1 rounded-lg bg-orange-600 py-3 font-semibold text-white active:bg-orange-700"
          >
            Tambah · {formatRupiah(hargaSatuan * jumlah)}
          </button>
        </div>
      </div>
    </div>
  )
}
