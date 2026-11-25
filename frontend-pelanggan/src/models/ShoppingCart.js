import { CartItem } from './CartItem.js'
import { Meja } from './Meja.js'

export class ShoppingCart {
  #idKeranjang
  #tanggalDibuat
  #meja
  #items

  constructor({ idKeranjang = null, tanggalDibuat = null, meja = null, items = [] } = {}) {
    this.#idKeranjang = idKeranjang ?? 'KRJ-LOCAL'
    this.#tanggalDibuat = tanggalDibuat ?? new Date().toISOString()
    this.#meja = meja ? (meja instanceof Meja ? meja : Meja.fromApi(meja)) : null
    this.#items = items
      .map((item) => (item instanceof CartItem ? item : CartItem.fromJSON(item)))
      .filter((item) => item.jumlah > 0)
  }

  get idKeranjang() {
    return this.#idKeranjang
  }

  get tanggalDibuat() {
    return this.#tanggalDibuat
  }

  get meja() {
    return this.#meja
  }

  get lines() {
    return [...this.#items]
  }

  get totalHarga() {
    return this.hitungTotal()
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

  hitungTotal() {
    return this.getTotalHarga()
  }

  withMeja(mejaBaru) {
    if (!mejaBaru) {
      return new ShoppingCart({
        idKeranjang: this.#idKeranjang,
        tanggalDibuat: this.#tanggalDibuat,
        meja: null,
        items: [],
      })
    }

    const mejaModel = mejaBaru instanceof Meja ? mejaBaru : Meja.fromApi(mejaBaru)
    const samaMeja = this.#meja?.id === mejaModel?.id

    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: mejaModel,
      items: samaMeja ? this.#items : [],
    })
  }

  addItem({ menu, jumlah = 1, condiments = [], catatan = '' }) {
    const kandidat = new CartItem({ menu, jumlah, condiments, catatan })
    if (!kandidat.menu.cekStok()) {
      throw new Error(`Menu ${kandidat.menu.namaMenu} sedang habis dan tidak dapat dipesan.`)
    }
    if (kandidat.jumlah <= 0) return this

    const ada = this.#items.find((item) => item.key === kandidat.key)
    const nextItems = ada
      ? this.#items.map((item) =>
          item.key === kandidat.key ? item.withJumlah(item.jumlah + kandidat.jumlah) : item,
        )
      : [...this.#items, kandidat]

    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: this.#meja,
      items: nextItems,
    })
  }

  tambahItem(item) {
    return this.addItem(item)
  }

  tambahMenu(item) {
    return this.addItem(item)
  }

  updateQuantity(key, jumlahBaru) {
    const nextItems = this.#items
      .map((item) => (item.key === key ? item.withJumlah(jumlahBaru) : item))
      .filter((item) => item.jumlah > 0)

    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: this.#meja,
      items: nextItems,
    })
  }

  ubahJumlah(key, jumlahBaru) {
    return this.updateQuantity(key, jumlahBaru)
  }

  updateNote(key, catatanBaru) {
    const target = this.#items.find((item) => item.key === key)
    if (!target) return this

    const updatedItem = target.withCatatan(catatanBaru)
    if (updatedItem.key === key) {
      return this
    }

    const existingSameKey = this.#items.find((item) => item.key === updatedItem.key && item.key !== key)
    let nextItems
    if (existingSameKey) {
      nextItems = this.#items
        .filter((item) => item.key !== key)
        .map((item) =>
          item.key === updatedItem.key
            ? item.withJumlah(item.jumlah + updatedItem.jumlah)
            : item,
        )
    } else {
      nextItems = this.#items.map((item) => (item.key === key ? updatedItem : item))
    }

    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: this.#meja,
      items: nextItems,
    })
  }

  ubahCatatan(key, catatanBaru) {
    return this.updateNote(key, catatanBaru)
  }

  removeItem(key) {
    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: this.#meja,
      items: this.#items.filter((item) => item.key !== key),
    })
  }

  hapusItem(key) {
    return this.removeItem(key)
  }

  clear() {
    return new ShoppingCart({
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
      meja: this.#meja,
      items: [],
    })
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
    if (!['kasir', 'online'].includes(metodePembayaran)) {
      throw new Error('Metode pembayaran harus online atau kasir.')
    }

    return {
      meja_id: this.#meja.id,
      nama_pengunjung: nama,
      metode_pembayaran: metodePembayaran,
      catatan: (catatan ?? '').trim() || null,
      items: this.#items.map((item) => item.toOrderItemPayload()),
    }
  }

  buatPesanan(options) {
    return this.toCheckoutPayload(options)
  }

  toJSON() {
    return {
      idKeranjang: this.#idKeranjang,
      tanggalDibuat: this.#tanggalDibuat,
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
        idKeranjang: parsed?.idKeranjang ?? null,
        tanggalDibuat: parsed?.tanggalDibuat ?? null,
        meja: parsed?.meja ?? null,
        items: parsed?.lines ?? [],
      })
    } catch {
      return new ShoppingCart()
    }
  }
}

export { ShoppingCart as Keranjang }
