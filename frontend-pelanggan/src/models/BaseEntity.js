import { formatRupiah, formatTanggal } from '../lib/format.js'

/**
 * Abstract base class untuk seluruh entitas domain di frontend.
 */
export class BaseEntity {
  #id

  constructor(id = null) {
    if (new.target === BaseEntity) {
      throw new TypeError('BaseEntity adalah kelas abstrak dan tidak dapat diinstansiasi langsung.')
    }
    this.#id = id
  }

  get id() {
    return this.#id
  }

  formatRupiah(nilai) {
    return formatRupiah(nilai)
  }

  formatTanggal(isoString) {
    return formatTanggal(isoString)
  }

  toJSON() {
    throw new Error(`Method toJSON() harus diimplementasikan oleh ${this.constructor.name}.`)
  }
}
