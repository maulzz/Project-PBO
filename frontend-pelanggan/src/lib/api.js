// Semua pemanggilan ke backend Laravel (/api/v1) ada di sini.
const BASE = import.meta.env.VITE_API_URL ?? 'https://project-pbo.maulzzserver.my.id/'

async function request(path, options = {}) {
  const res = await fetch(`${BASE}${path}`, {
    headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
    ...options,
  })
  const body = await res.json().catch(() => ({}))

  if (!res.ok) {
    const err = new Error(body.message ?? 'Terjadi kesalahan, coba lagi.')
    err.status = res.status
    err.errors = body.errors ?? null // 422 dari Laravel: { field: [pesan] }
    throw err
  }
  return body.data ?? body
}

export const getMeja = (qrCode) => request(`/meja/${encodeURIComponent(qrCode)}`)
export const getKategoris = () => request('/kategoris')
export const getMenus = () => request('/menus')
export const createPesanan = (payload) =>
  request('/pesanans', { method: 'POST', body: JSON.stringify(payload) })
export const getPesanan = (nomor) => request(`/pesanans/${nomor}`)
export const cekPembayaran = (nomor) =>
  request(`/pesanans/${nomor}/cek-pembayaran`, { method: 'POST' })
