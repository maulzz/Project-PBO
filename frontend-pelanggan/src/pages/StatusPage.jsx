import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { formatRupiah } from '../lib/format.js'
import { cafeApiService } from '../services/CafeApiService.js'
import { PembayaranFactory } from '../services/pembayaran/PembayaranFactory.js'

// Menampilkan status pesanan & status pembayaran, auto-refresh tiap 5 detik sampai status final.
export default function StatusPage() {
  const { nomor } = useParams()
  const [pesanan, setPesanan] = useState(null)
  const [error, setError] = useState('')
  const [sedangCek, setSedangCek] = useState(false)

  const sinkronStatus = useCallback(async () => {
    let data = await cafeApiService.getPesanan(nomor)
    if (data.needsMidtransSync()) {
      data = await cafeApiService.cekPembayaran(nomor).catch(() => data)
    }
    return data
  }, [nomor])

  useEffect(() => {
    let batal = false
    let timerId = null

    async function muatDanJadwalkan() {
      try {
        const data = await sinkronStatus()
        if (batal) return
        setPesanan(data)

        if (!data.isFinal()) {
          timerId = setTimeout(muatDanJadwalkan, 5000)
        }
      } catch (err) {
        if (!batal) setError(err.message)
      }
    }

    muatDanJadwalkan()
    return () => {
      batal = true
      if (timerId) clearTimeout(timerId)
    }
  }, [sinkronStatus])

  async function handleLanjutkanBayar() {
    if (!pesanan) return
    setSedangCek(true)
    try {
      const strategy = PembayaranFactory.create('online', cafeApiService)
      const hasil = await strategy.prosesPembayaran(pesanan)
      if (hasil.pesanan) {
        setPesanan(hasil.pesanan)
      }
    } finally {
      setSedangCek(false)
    }
  }

  async function handleCekPembayaranManual() {
    setSedangCek(true)
    try {
      const updated = await cafeApiService.cekPembayaran(nomor)
      setPesanan(updated)
    } catch (err) {
      setError(err.message)
    } finally {
      setSedangCek(false)
    }
  }

  if (error) {
    return (
      <div className="p-8 text-center">
        <p className="text-red-600">{error}</p>
        <Link to="/order" className="mt-4 inline-block text-sm text-orange-700 underline">
          Kembali ke Menu
        </Link>
      </div>
    )
  }

  if (!pesanan) {
    return <p className="p-8 text-center text-stone-500">Memuat pesanan…</p>
  }

  const bayar = pesanan.pembayaran

  return (
    <div className="space-y-4 p-4 pb-10">
      <div className="rounded-2xl bg-orange-600 p-5 text-center text-white">
        <p className="text-sm opacity-80">Nomor Pesanan</p>
        <p className="text-2xl font-bold tracking-wide">{pesanan.nomorPesanan}</p>
        <p className="mt-1 text-xs opacity-90">
          Pemesan: {pesanan.namaPengunjung} • {pesanan.formatTanggal(pesanan.tanggalPesanan)}
        </p>
        {bayar?.isKasir() && bayar.isMenunggu() && !pesanan.isDibatalkan() && (
          <p className="mt-2 rounded-lg bg-white/15 px-3 py-1.5 text-sm">
            Silakan sebutkan nomor ini di kasir untuk membayar.
          </p>
        )}
      </div>

      <dl className="grid grid-cols-2 gap-3 text-sm">
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Meja</dt>
          <dd className="mt-0.5 font-semibold">{pesanan.nomorMeja}</dd>
        </div>
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Status Pesanan</dt>
          <dd className="mt-1">
            <span
              className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${pesanan.getStatusBadgeClass()}`}
            >
              {pesanan.statusPesanan}
            </span>
          </dd>
        </div>
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Pembayaran ({bayar?.getLabelMetode() ?? '-'})</dt>
          <dd className="mt-1">
            <span
              className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${bayar?.getStatusBadgeClass() ?? ''}`}
            >
              {bayar?.statusPembayaran ?? '-'}
            </span>
          </dd>
        </div>
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Total</dt>
          <dd className="mt-0.5 font-semibold text-orange-700">
            {formatRupiah(pesanan.totalHarga)}
          </dd>
        </div>
      </dl>

      {pesanan.catatan && (
        <div className="rounded-xl bg-white p-3 text-sm shadow-sm">
          <p className="text-xs font-semibold text-stone-500">Catatan Pesanan</p>
          <p className="mt-0.5 italic text-stone-700">“{pesanan.catatan}”</p>
        </div>
      )}

      {bayar?.hasSnapPayment() && !pesanan.isDibatalkan() && (
        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <button
            type="button"
            disabled={sedangCek}
            onClick={handleLanjutkanBayar}
            className="rounded-lg bg-orange-600 py-3 text-center text-sm font-semibold text-white disabled:opacity-60"
          >
            {sedangCek ? 'Membuka Pembayaran…' : 'Bayar Sekarang'}
          </button>
          <button
            type="button"
            disabled={sedangCek}
            onClick={handleCekPembayaranManual}
            className="rounded-lg border border-orange-600 bg-white py-3 text-center text-sm font-semibold text-orange-700 disabled:opacity-60"
          >
            Cek Status Pembayaran
          </button>
        </div>
      )}

      <ul className="space-y-2">
        {pesanan.items.map((item) => {
          const condimentSummary = item.getCondimentSummary()
          return (
            <li key={item.id} className="rounded-xl bg-white p-3 text-sm shadow-sm">
              <div className="flex justify-between gap-2">
                <span className="font-medium">
                  {item.jumlah}× {item.namaMenu}
                </span>
                <span className="font-semibold">{formatRupiah(item.subtotal)}</span>
              </div>
              {condimentSummary && (
                <p className="mt-0.5 text-xs text-stone-500">+ {condimentSummary}</p>
              )}
              {item.catatan && (
                <p className="mt-0.5 text-xs italic text-stone-500">“{item.catatan}”</p>
              )}
            </li>
          )
        })}
      </ul>

      <Link to="/order" className="block text-center text-sm text-orange-700 underline">
        Pesan lagi
      </Link>
    </div>
  )
}
