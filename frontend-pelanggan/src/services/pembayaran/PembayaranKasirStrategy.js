import { MetodePembayaranStrategy } from './MetodePembayaranStrategy.js'

export class PembayaranKasirStrategy extends MetodePembayaranStrategy {
  getNamaMetode() {
    return 'kasir'
  }

  getLabel() {
    return 'Bayar di Kasir'
  }

  async prosesPembayaran(pesanan) {
    return {
      status: pesanan?.pembayaran?.statusPembayaran ?? 'menunggu',
      pesanan,
    }
  }
}

export { PembayaranKasirStrategy as PembayaranKasir }
