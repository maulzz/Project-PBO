import { useCallback, useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { useCart } from '../context/useCart.js'
import { formatRupiah } from '../lib/format.js'
import { Pesanan } from '../models/Pesanan.js'
import { cafeApiService } from '../services/CafeApiService.js'
import { PembayaranFactory } from '../services/pembayaran/PembayaranFactory.js'

// KF-08, KF-10, KF-11, KF-14, Batasan 4.3.1:
// Menampilkan nomor pesanan unik, tahapan status pesanan (Pesanan Masuk -> Diterima -> Diproses -> Selesai),
// status pembayaran (online / kasir), serta sinkronisasi pembayaran Midtrans.
export default function StatusPage() {
  const { nomor } = useParams()
  const { meja, catatPesanan } = useCart()
  const [pesanan, setPesanan] = useState(null)
  const [error, setError] = useState('')
  const [sedangCek, setSedangCek] = useState(false)

  const orderPath = meja?.qrCode ? `/order?meja=${encodeURIComponent(meja.qrCode)}` : '/order'

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
        catatPesanan(data)

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
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [sinkronStatus])

  async function handleLanjutkanBayar() {
    if (!pesanan) return
    setSedangCek(true)
    try {
      const strategy = PembayaranFactory.create('online', cafeApiService)
      const hasil = await strategy.prosesPembayaran(pesanan)
      if (hasil.pesanan) {
        setPesanan(hasil.pesanan)
        catatPesanan(hasil.pesanan)
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
      catatPesanan(updated)
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
        <Link to={orderPath} className="mt-4 inline-block text-sm text-orange-700 underline">
          Kembali ke Menu
        </Link>
      </div>
    )
  }

  if (!pesanan) {
    return <p className="p-8 text-center text-stone-500">Memuat pesanan…</p>
  }

  const bayar = pesanan.pembayaran
  const tahapanList = Pesanan.TAHAPAN_STATUS
  const tahapanIdx = pesanan.getTahapanIndex()

  return (
    <div className="space-y-4 p-4 pb-10">
      <div className="rounded-2xl bg-orange-600 p-5 text-center text-white shadow-sm">
        <p className="text-sm opacity-80">Nomor Pesanan Unik</p>
        <p className="text-2xl font-bold tracking-wide">{pesanan.nomorPesanan}</p>
        <p className="mt-1 text-xs opacity-90">
          Pemesan: {pesanan.namaPengunjung} • {pesanan.formatTanggal(pesanan.tanggalPesanan)}
        </p>
        {bayar?.isKasir() && bayar.isMenunggu() && !pesanan.isDibatalkan() && (
          <p className="mt-3 rounded-lg bg-white/15 px-3 py-2 text-sm font-medium">
            Silakan sebutkan nomor pesanan <strong>{pesanan.nomorPesanan}</strong> di kasir untuk
            melakukan pembayaran.
          </p>
        )}
      </div>

      {/* Tahapan Status Pesanan sesuai PDF Bab 4.1.2 & KF-14 */}
      <div className="rounded-xl bg-white p-4 shadow-sm">
        <div className="flex items-center justify-between">
          <p className="text-xs font-semibold uppercase tracking-wider text-stone-500">
            Tahapan Pesanan
          </p>
          <span
            className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold ${pesanan.getStatusBadgeClass()}`}
          >
            {pesanan.statusPesanan}
          </span>
        </div>

        {pesanan.isDibatalkan() ? (
          <div className="mt-3 rounded-lg bg-red-50 p-3 text-xs text-red-700">
            Pesanan ini telah dibatalkan oleh pelayan.
          </div>
        ) : (
          <ol className="mt-3 grid grid-cols-4 gap-1.5 text-center text-[11px]">
            {tahapanList.map((tahap, idx) => {
              const selesaiAtauAktif = tahapanIdx >= idx
              const sedangAktif = tahapanIdx === idx
              return (
                <li
                  key={tahap}
                  className={`rounded-lg px-1.5 py-2 font-medium transition ${
                    sedangAktif
                      ? 'bg-orange-600 text-white shadow-2xs'
                      : selesaiAtauAktif
                        ? 'bg-emerald-50 text-emerald-800'
                        : 'bg-stone-100 text-stone-400'
                  }`}
                >
                  <div className="text-xs font-bold">{idx + 1}</div>
                  <div className="mt-0.5 leading-tight">{tahap}</div>
                </li>
              )
            })}
          </ol>
        )}
      </div>

      <dl className="grid grid-cols-2 gap-3 text-sm">
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Nomor Meja</dt>
          <dd className="mt-0.5 font-semibold">{pesanan.nomorMeja}</dd>
        </div>
        <div className="rounded-xl bg-white p-3 shadow-sm">
          <dt className="text-stone-500">Total Tagihan</dt>
          <dd className="mt-0.5 font-semibold text-orange-700">
            {formatRupiah(pesanan.totalHarga)}
          </dd>
        </div>
        <div className="col-span-2 rounded-xl bg-white p-3 shadow-sm">
          <div className="flex items-center justify-between">
            <div>
              <dt className="text-stone-500">Metode Pembayaran</dt>
              <dd className="mt-0.5 font-semibold">{bayar?.getLabelMetode() ?? '-'}</dd>
            </div>
            <div className="text-right">
              <dt className="text-stone-500">Status Pembayaran</dt>
              <dd className="mt-1">
                <span
                  className={`inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize ${bayar?.getStatusBadgeClass() ?? ''}`}
                >
                  {bayar?.statusPembayaran ?? '-'}
                </span>
              </dd>
            </div>
          </div>
          {bayar?.waktuBayar && (
            <p className="mt-2 border-t border-stone-100 pt-2 text-xs text-stone-500">
              Waktu bayar: {pesanan.formatTanggal(bayar.waktuBayar)}
            </p>
          )}
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

      <div className="rounded-xl bg-white p-3 shadow-sm">
        <p className="mb-2 text-xs font-semibold uppercase tracking-wider text-stone-500">
          Rincian Menu Dipesan
        </p>
        <ul className="divide-y divide-stone-100">
          {pesanan.items.map((item) => {
            const condimentSummary = item.getCondimentSummary()
            return (
              <li key={item.id} className="py-2.5 text-sm first:pt-0 last:pb-0">
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
      </div>

      {/* Informasi Batasan 4.3.1 di PDF Proposal */}
      {!pesanan.isDibatalkan() && !pesanan.isSelesai() && (
        <p className="rounded-xl border border-stone-200 bg-stone-100/80 p-3 text-center text-xs text-stone-600">
          Sesuai ketentuan cafe, pesanan yang telah dikirim/diterima pelayan tidak dapat diubah atau
          dibatalkan secara mandiri. Silakan hubungi pelayan jika memerlukan bantuan.
        </p>
      )}

      <Link
        to={orderPath}
        className="block rounded-xl border border-orange-600 bg-white py-3 text-center text-sm font-semibold text-orange-700 hover:bg-orange-50"
      >
        + Pesan Menu Lagi
      </Link>
    </div>
  )
}
