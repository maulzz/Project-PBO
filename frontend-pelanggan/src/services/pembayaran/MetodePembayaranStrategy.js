/**
 * Abstract strategy untuk alur penyelesaian pembayaran setelah checkout di frontend.
 * Mencerminkan interface MetodePembayaran pada backend Laravel.
 */
export class MetodePembayaranStrategy {
  constructor() {
    if (new.target === MetodePembayaranStrategy) {
      throw new TypeError('MetodePembayaranStrategy adalah kelas abstrak.')
    }
  }

  getNamaMetode() {
    throw new Error('Method getNamaMetode() harus diimplementasikan.')
  }

  getLabel() {
    throw new Error('Method getLabel() harus diimplementasikan.')
  }

  /**
   * @param {import('../../models/Pesanan.js').Pesanan} pesanan
   * @returns {Promise<{ status: string, pesanan: import('../../models/Pesanan.js').Pesanan }>}
   */
  async prosesPembayaran(pesanan) {
    throw new Error(`Method prosesPembayaran() harus diimplementasikan untuk ${pesanan?.nomorPesanan ?? ''}.`)
  }
}
