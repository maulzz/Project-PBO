import { CartItem } from './CartItem.js'
import { Meja } from './Meja.js'

export class ShoppingCart {
  #meja
  #items

  constructor({ meja = null, items = [] } = {}) {
    this.#meja = meja ? (meja instanceof Meja ? meja : Meja.fromApi(meja)) : null
    this.#items = items
      .map((item) => (item instanceof CartItem ? item : CartItem.fromJSON(item)))
      .filter((item) => item.jumlah > 0)
  }

  get meja() {
    return this.#meja
  }

  get lines() {
    return [...this.#items]
  }

  isEmpty() {
    return this.#items.length === 0
  }

  getTotalItems() {
    return this.#items.reduce((total, item) => total + item.jumlah, 0)
  }

  getTotalHarga() {
    return this.#items.reduce((total, item) => total + item.getSubtotal(), 0)
  }

  withMeja(mejaBaru) {
    const mejaModel = mejaBaru instanceof Meja ? mejaBaru : Meja.fromApi(mejaBaru)
    const samaMeja = this.#meja?.id === mejaModel?.id

    return new ShoppingCart({
      meja: mejaModel,
      items: samaMeja ? this.#items : [],
    })
  }

  addItem({ menu, jumlah = 1, condiments = [], catatan = '' }) {
    const kandidat = new CartItem({ menu, jumlah, condiments, catatan })
    if (kandidat.jumlah <= 0) return this

    const ada = this.#items.find((item) => item.key === kandidat.key)
    const nextItems = ada
      ? this.#items.map((item) =>
          item.key === kandidat.key ? item.withJumlah(item.jumlah + kandidat.jumlah) : item,
        )
      : [...this.#items, kandidat]

    return new ShoppingCart({ meja: this.#meja, items: nextItems })
  }

  updateQuantity(key, jumlahBaru) {
    const nextItems = this.#items
      .map((item) => (item.key === key ? item.withJumlah(jumlahBaru) : item))
      .filter((item) => item.jumlah > 0)

    return new ShoppingCart({ meja: this.#meja, items: nextItems })
  }

  removeItem(key) {
    return new ShoppingCart({
      meja: this.#meja,
      items: this.#items.filter((item) => item.key !== key),
    })
  }

  clear() {
    return new ShoppingCart({ meja: this.#meja, items: [] })
  }

  /**
   * Membangun payload sesuai StorePesananRequest pada backend Laravel.
   */
  toCheckoutPayload({ namaPengunjung, metodePembayaran, catatan = '' }) {
    if (!this.#meja?.id) {
      throw new Error('Meja belum teridentifikasi. Silakan scan QR Code di meja Anda.')
    }
    if (this.isEmpty()) {
      throw new Error('Keranjang pesanan tidak boleh kosong.')
    }
    const nama = (namaPengunjung ?? '').trim()
    if (!nama) {
      throw new Error('Nama pengunjung wajib diisi.')
    }

    return {
      meja_id: this.#meja.id,
      nama_pengunjung: nama,
      metode_pembayaran: metodePembayaran,
      catatan: (catatan ?? '').trim() || null,
      items: this.#items.map((item) => item.toOrderItemPayload()),
    }
  }

  toJSON() {
    return {
      meja: this.#meja ? this.#meja.toJSON() : null,
      lines: this.#items.map((item) => item.toJSON()),
    }
  }

  serialize() {
    return JSON.stringify(this.toJSON())
  }

  static deserialize(raw) {
    if (!raw) return new ShoppingCart()
    try {
      const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw
      return new ShoppingCart({
        meja: parsed?.meja ?? null,
        items: parsed?.lines ?? [],
      })
    } catch {
      return new ShoppingCart()
    }
  }
}
