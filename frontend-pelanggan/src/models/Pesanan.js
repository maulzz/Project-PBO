import { BaseEntity } from './BaseEntity.js'
import { PembayaranInfo } from './PembayaranInfo.js'
import { PesananItem } from './PesananItem.js'

export class Pesanan extends BaseEntity {
  static TAHAPAN_STATUS = ['Pesanan Masuk', 'Diterima', 'Diproses', 'Selesai']

  #nomorPesanan
  #nomorMeja
  #namaPengunjung
  #statusPesanan
  #totalHarga
  #tanggalPesanan
  #catatan
  #pembayaran
  #items

  constructor({
    id,
    nomor_pesanan,
    nomor_meja = '-',
    nama_pengunjung = '',
    status_pesanan = 'Pesanan Masuk',
    total_harga = 0,
    tanggal_pesanan = null,
    catatan = null,
    pembayaran = null,
    items = [],
  }) {
    super(id)
    this.#nomorPesanan = nomor_pesanan ?? ''
    this.#nomorMeja = nomor_meja ?? '-'
    this.#namaPengunjung = nama_pengunjung ?? ''
    this.#statusPesanan = status_pesanan ?? 'Pesanan Masuk'
    this.#totalHarga = Number(total_harga ?? 0)
    this.#tanggalPesanan = tanggal_pesanan
    this.#catatan = catatan
    this.#pembayaran = PembayaranInfo.fromApi(pembayaran)
    this.#items = items.map((i) => (i instanceof PesananItem ? i : PesananItem.fromApi(i)))
  }

  get idPesanan() {
    return this.id
  }

  get nomor_pesanan() {
    return this.#nomorPesanan
  }

  get nomorPesanan() {
    return this.#nomorPesanan
  }

  get nomor_meja() {
    return this.#nomorMeja
  }

  get nomorMeja() {
    return this.#nomorMeja
  }

  get nama_pengunjung() {
    return this.#namaPengunjung
  }

  get namaPengunjung() {
    return this.#namaPengunjung
  }

  get status_pesanan() {
    return this.#statusPesanan
  }

  get statusPesanan() {
    return this.#statusPesanan
  }

  get total_harga() {
    return this.#totalHarga
  }

  get totalHarga() {
    return this.#totalHarga
  }

  get tanggal_pesanan() {
    return this.#tanggalPesanan
  }

  get tanggalPesanan() {
    return this.#tanggalPesanan
  }

  get catatan() {
    return this.#catatan
  }

  get pembayaran() {
    return this.#pembayaran
  }

  get items() {
    return [...this.#items]
  }

  isSelesai() {
    return this.#statusPesanan === 'Selesai'
  }

  isDibatalkan() {
    return this.#statusPesanan === 'Dibatalkan'
  }

  /**
   * Sesuai aturan bisnis BE (Pesanan::isDapatDibatalkan):
   * Pesanan hanya dapat dibatalkan oleh pelayan saat status 'Pesanan Masuk' atau 'Diterima'.
   */
  isDapatDibatalkan() {
    return ['Pesanan Masuk', 'Diterima'].includes(this.#statusPesanan)
  }

  /**
   * Sesuai Batasan 4.3.1 di PDF Proposal:
   * Pengunjung tidak dapat mengubah atau membatalkan pesanan secara mandiri.
   */
  canCancelMandiri() {
    return false
  }

  hitungTotalHarga() {
    if (this.#items.length === 0) return this.#totalHarga
    return this.#items.reduce((sum, item) => sum + item.hitungSubtotal(), 0)
  }

  getTahapanStatus() {
    return [...Pesanan.TAHAPAN_STATUS]
  }

  getTahapanIndex() {
    if (this.isDibatalkan()) return -1
    return Pesanan.TAHAPAN_STATUS.indexOf(this.#statusPesanan)
  }

  /**
   * Polling status dapat berhenti ketika pesanan sudah Selesai/Dibatalkan
   * dan pembayaran tidak lagi berstatus menunggu (atau pesanan dibatalkan).
   */
  isFinal() {
    if (this.isDibatalkan()) return true
    if (this.isSelesai() && (!this.#pembayaran || !this.#pembayaran.isMenunggu())) {
      return true
    }
    return false
  }

  needsMidtransSync() {
    return Boolean(
      this.#pembayaran?.isOnline() &&
        this.#pembayaran?.isMenunggu() &&
        !this.isDibatalkan(),
    )
  }

  getStatusBadgeClass() {
    switch (this.#statusPesanan) {
      case 'Pesanan Masuk':
        return 'bg-amber-100 text-amber-800'
      case 'Diterima':
        return 'bg-sky-100 text-sky-800'
      case 'Diproses':
        return 'bg-orange-100 text-orange-800'
      case 'Selesai':
        return 'bg-emerald-100 text-emerald-800'
      case 'Dibatalkan':
        return 'bg-red-100 text-red-700'
      default:
        return 'bg-stone-100 text-stone-700'
    }
  }

  static fromApi(data) {
    return new Pesanan(data)
  }

  toJSON() {
    return {
      id: this.id,
      nomor_pesanan: this.#nomorPesanan,
      nomor_meja: this.#nomorMeja,
      nama_pengunjung: this.#namaPengunjung,
      status_pesanan: this.#statusPesanan,
      total_harga: this.#totalHarga,
      tanggal_pesanan: this.#tanggalPesanan,
      catatan: this.#catatan,
      pembayaran: this.#pembayaran?.toJSON() ?? null,
      items: this.#items.map((i) => i.toJSON()),
    }
  }
}
