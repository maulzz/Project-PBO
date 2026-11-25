const rupiah = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  maximumFractionDigits: 0,
})

const tanggal = new Intl.DateTimeFormat('id-ID', {
  day: '2-digit',
  month: 'short',
  year: 'numeric',
  hour: '2-digit',
  minute: '2-digit',
})

export const formatRupiah = (angka) => rupiah.format(Number(angka ?? 0))

export const formatTanggal = (isoString) => {
  if (!isoString) return '-'
  const date = new Date(isoString)
  return Number.isNaN(date.getTime()) ? '-' : tanggal.format(date)
}
