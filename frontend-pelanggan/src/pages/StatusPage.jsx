import { useEffect, useState } from 'react'
import { Link, useParams } from 'react-router-dom'
import { cekPembayaran, getPesanan } from '../lib/api'
import { formatRupiah } from '../lib/format'

// Menampilkan nomor pesanan (untuk disebutkan ke kasir) + status, diperbarui tiap 5 detik.
export default function StatusPage() {
  const { nomor } = useParams()
  const [p, setP] = useState(null)
  const [error, setError] = useState('')

  useEffect(() => {
    let batal = false
    async function muat() {
      try {
        let data = await getPesanan(nomor)
        // pembayaran online yang belum lunas -> minta backend sinkron ke Midtrans
        if (data.pembayaran?.metode_pembayaran === 'online' && data.pembayaran.status_pembayaran === 'menunggu') {
          data = await cekPembayaran(nomor).catch(() => data)
        }
        if (!batal) setP(data)
      } catch (e) {
        if (!batal) setError(e.message)
      }
    }
    muat()
    const t = setInterval(muat, 5000)
    return () => { batal = true; clearInterval(t) }
  }, [nomor])

  if (error) return <p className="p-8 text-center text-red-600">{error}</p>
  if (!p) return <p className="p-8 text-center text-stone-500">Memuat pesanan…</p>

  const bayar = p.pembayaran
  return (
    <div className="space-y-4 p-4">
      <div className="rounded-2xl bg-orange-600 p-5 text-center text-white">
        <p className="text-sm opacity-80">Nomor Pesanan</p>
        <p className="text-2xl font-bold tracking-wide">{p.nomor_pesanan}</p>
        {bayar?.metode_pembayaran === 'kasir' && bayar.status_pembayaran !== 'lunas' && (
          <p className="mt-2 text-sm">Sebutkan nomor ini di kasir untuk membayar.</p>
        )}
      </div>

      <dl className="grid grid-cols-2 gap-3 text-sm">
        <div className="rounded-xl bg-white p-3"><dt className="text-stone-500">Meja</dt><dd className="font-semibold">{p.nomor_meja}</dd></div>
        <div className="rounded-xl bg-white p-3"><dt className="text-stone-500">Status Pesanan</dt><dd className="font-semibold">{p.status_pesanan}</dd></div>
        <div className="rounded-xl bg-white p-3"><dt className="text-stone-500">Pembayaran</dt><dd className="font-semibold capitalize">{bayar?.status_pembayaran}</dd></div>
        <div className="rounded-xl bg-white p-3"><dt className="text-stone-500">Total</dt><dd className="font-semibold">{formatRupiah(p.total_harga)}</dd></div>
      </dl>

      {bayar?.snap_url && (
        <a href={bayar.snap_url} className="block rounded-lg bg-orange-600 py-3 text-center font-semibold text-white">
          Lanjutkan Pembayaran
        </a>
      )}

      <ul className="space-y-2">
        {p.items?.map((i) => (
          <li key={i.id} className="flex justify-between rounded-xl bg-white p-3 text-sm">
            <span>{i.jumlah}× {i.nama_menu}{i.condiments.length > 0 && ` (+${i.condiments.map((c) => c.nama_condiment).join(', ')})`}</span>
            <span>{formatRupiah(i.subtotal)}</span>
          </li>
        ))}
      </ul>

      <Link to="/order" className="block text-center text-sm text-orange-700 underline">Pesan lagi</Link>
    </div>
  )
}
