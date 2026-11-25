import { BaseEntity } from './BaseEntity.js'
import { Pesanan } from './Pesanan.js'
import { ShoppingCart } from './ShoppingCart.js'

/**
 * Kelas Pengunjung sesuai Bab 7.2, 7.3, 8.1, 8.2, dan 8.3.A pada PDF Proposal PBO.
 * Catatan Bab 8.3.A: Kelas Pengunjung tidak diturunkan dari Pengguna karena
 * pengunjung melakukan pemesanan melalui QR Code tanpa proses login.
 */
export class Pengunjung extends BaseEntity {
  #namaPengunjung
  #keranjang
  #riwayatPesanan

  constructor({
    id = null,
    namaPengunjung = '',
    keranjang = null,
    riwayatPesanan = [],
  } = {}) {
    super(id)
    this.#namaPengunjung = (namaPengunjung ?? '').trim()
    this.#keranjang = keranjang instanceof ShoppingCart ? keranjang : new ShoppingCart(keranjang ?? {})
    this.#riwayatPesanan = Array.isArray(riwayatPesanan)
      ? riwayatPesanan.map((p) => (p instanceof Pesanan ? p : Pesanan.fromApi(p)))
      : []
  }

  get namaPengunjung() {
    return this.#namaPengunjung
  }

  get keranjang() {
    return this.#keranjang
  }

  get meja() {
    return this.#keranjang.meja
  }

  get riwayatPesanan() {
    return [...this.#riwayatPesanan]
  }

  withNamaPengunjung(namaBaru) {
    return new Pengunjung({
      id: this.id,
      namaPengunjung: namaBaru,
      keranjang: this.#keranjang,
      riwayatPesanan: this.#riwayatPesanan,
    })
  }

  withKeranjang(keranjangBaru) {
    return new Pengunjung({
      id: this.id,
      namaPengunjung: this.#namaPengunjung,
      keranjang: keranjangBaru,
      riwayatPesanan: this.#riwayatPesanan,
    })
  }

  withPesananBaru(pesananBaru) {
    const model = pesananBaru instanceof Pesanan ? pesananBaru : Pesanan.fromApi(pesananBaru)
    const filtered = this.#riwayatPesanan.filter((p) => p.nomorPesanan !== model.nomorPesanan)
    return new Pengunjung({
      id: this.id,
      namaPengunjung: model.namaPengunjung || this.#namaPengunjung,
      keranjang: this.#keranjang,
      riwayatPesanan: [model, ...filtered].slice(0, 10),
    })
  }

  /**
   * Method lihatMenu() sesuai Bab 8.1 PDF Proposal.
   */
  async lihatMenu(apiService, filter = {}) {
    return apiService.getMenus(filter)
  }

  /**
   * Method buatPesanan() sesuai Bab 8.1 PDF Proposal.
   */
  async buatPesanan(apiService, { namaPengunjung = null, metodePembayaran = 'kasir', catatan = '' } = {}) {
    const namaFinal = (namaPengunjung ?? this.#namaPengunjung).trim()
    const payload = this.#keranjang.buatPesanan({
      namaPengunjung: namaFinal,
      metodePembayaran,
      catatan,
    })
    return apiService.createPesanan(payload)
  }

  lihatRiwayatPesanan() {
    return [...this.#riwayatPesanan]
  }

  toJSON() {
    return {
      id: this.id,
      namaPengunjung: this.#namaPengunjung,
      keranjang: this.#keranjang.toJSON(),
      riwayatPesanan: this.#riwayatPesanan.map((p) => p.toJSON()),
    }
  }
}
