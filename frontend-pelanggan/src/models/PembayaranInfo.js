import { BaseEntity } from './BaseEntity.js'

export class PembayaranInfo extends BaseEntity {
  #metodePembayaran
  #totalBayar
  #statusPembayaran
  #transaksiRef
  #waktuBayar
  #snapToken
  #snapUrl
  #clientKey

  constructor({
    id = null,
    metode_pembayaran = 'kasir',
    total_bayar = 0,
    status_pembayaran = 'menunggu',
    transaksi_ref = null,
    waktu_bayar = null,
    snap_token = null,
    snap_url = null,
    client_key = null,
  }) {
    super(id)
    this.#metodePembayaran = metode_pembayaran
    this.#totalBayar = Number(total_bayar ?? 0)
    this.#statusPembayaran = status_pembayaran
    this.#transaksiRef = transaksi_ref
    this.#waktuBayar = waktu_bayar
    this.#snapToken = snap_token
    this.#snapUrl = snap_url
    this.#clientKey = client_key
  }

  get idPembayaran() {
    return this.id
  }

  get metode_pembayaran() {
    return this.#metodePembayaran
  }

  get metodePembayaran() {
    return this.#metodePembayaran
  }

  get total_bayar() {
    return this.#totalBayar
  }

  get totalBayar() {
    return this.#totalBayar
  }

  get status_pembayaran() {
    return this.#statusPembayaran
  }

  get statusPembayaran() {
    return this.#statusPembayaran
  }

  get transaksi_ref() {
    return this.#transaksiRef
  }

  get transaksiRef() {
    return this.#transaksiRef
  }

  get waktu_bayar() {
    return this.#waktuBayar
  }

  get waktuBayar() {
    return this.#waktuBayar
  }

  get snap_token() {
    return this.#snapToken
  }

  get snapToken() {
    return this.#snapToken
  }

  get snap_url() {
    return this.#snapUrl
  }

  get snapUrl() {
    return this.#snapUrl
  }

  get client_key() {
    return this.#clientKey
  }

  get clientKey() {
    return this.#clientKey
  }

  isLunas() {
    return this.#statusPembayaran === 'lunas'
  }

  isMenunggu() {
    return this.#statusPembayaran === 'menunggu'
  }

  isDibatalkan() {
    return this.#statusPembayaran === 'dibatalkan'
  }

  isGagal() {
    return this.#statusPembayaran === 'gagal'
  }

  isOnline() {
    return this.#metodePembayaran === 'online'
  }

  isKasir() {
    return this.#metodePembayaran === 'kasir'
  }

  hasSnapPayment() {
    return this.isOnline() && this.isMenunggu() && Boolean(this.#snapToken || this.#snapUrl)
  }

  getLabelMetode() {
    return this.isOnline() ? 'Online (QRIS / e-Wallet)' : 'Tunai di Kasir'
  }

  getStatusBadgeClass() {
    switch (this.#statusPembayaran) {
      case 'lunas':
        return 'bg-emerald-100 text-emerald-800'
      case 'menunggu':
        return 'bg-amber-100 text-amber-800'
      case 'gagal':
      case 'dibatalkan':
        return 'bg-red-100 text-red-700'
      default:
        return 'bg-stone-100 text-stone-700'
    }
  }

  static fromApi(data) {
    return data ? new PembayaranInfo(data) : null
  }

  updateStatusPembayaran(statusBaru) {
    return new PembayaranInfo({
      ...this.toJSON(),
      status_pembayaran: statusBaru,
    })
  }

  async prosesPembayaran(strategy, pesanan) {
    if (!strategy || typeof strategy.prosesPembayaran !== 'function') {
      throw new Error('Strategy MetodePembayaran tidak valid.')
    }
    return strategy.prosesPembayaran(pesanan)
  }

  toJSON() {
    return {
      id: this.id,
      metode_pembayaran: this.#metodePembayaran,
      total_bayar: this.#totalBayar,
      status_pembayaran: this.#statusPembayaran,
      transaksi_ref: this.#transaksiRef,
      waktu_bayar: this.#waktuBayar,
      snap_token: this.#snapToken,
      snap_url: this.#snapUrl,
      client_key: this.#clientKey,
    }
  }
}

export { PembayaranInfo as Pembayaran }
