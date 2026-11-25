import { Condiment } from './Condiment.js'
import { MenuItem } from './MenuItem.js'

export class CartItem {
  #key
  #menu
  #jumlah
  #condiments
  #catatan

  constructor({ menu, jumlah = 1, condiments = [], catatan = '' }) {
    this.#menu = menu instanceof MenuItem ? menu : MenuItem.fromApi(menu)
    this.#jumlah = CartItem.clampJumlah(jumlah)
    this.#condiments = condiments.map((c) => (c instanceof Condiment ? c : Condiment.fromApi(c)))
    this.#catatan = (catatan ?? '').trim()
    this.#key = CartItem.createKey(this.#menu.id, this.#condiments, this.#catatan)
  }

  static clampJumlah(jumlah) {
    const angka = Number(jumlah ?? 0)
    if (!Number.isFinite(angka)) return 0
    return Math.min(99, Math.max(0, Math.trunc(angka)))
  }

  static createKey(menuId, condiments = [], catatan = '') {
    const condimentKey = condiments
      .map((c) => c.id)
      .sort((a, b) => a - b)
      .join('.')
    return [menuId, condimentKey, (catatan ?? '').trim()].join('|')
  }

  get key() {
    return this.#key
  }

  get idDetail() {
    return this.#key
  }

  get menu() {
    return this.#menu
  }

  get jumlah() {
    return this.#jumlah
  }

  get condiments() {
    return [...this.#condiments]
  }

  get catatan() {
    return this.#catatan
  }

  get subtotal() {
    return this.hitungSubtotal()
  }

  getHargaSatuan() {
    const tambahan = this.#condiments.reduce((sum, c) => sum + c.hargaCondiment, 0)
    return this.#menu.harga + tambahan
  }

  hitungSubtotal() {
    return this.getHargaSatuan() * this.#jumlah
  }

  getSubtotal() {
    return this.hitungSubtotal()
  }

  withJumlah(jumlahBaru) {
    return new CartItem({
      menu: this.#menu,
      jumlah: jumlahBaru,
      condiments: this.#condiments,
      catatan: this.#catatan,
    })
  }

  ubahJumlah(jumlahBaru) {
    return this.withJumlah(jumlahBaru)
  }

  withCatatan(catatanBaru) {
    return new CartItem({
      menu: this.#menu,
      jumlah: this.#jumlah,
      condiments: this.#condiments,
      catatan: String(catatanBaru ?? '').slice(0, 500),
    })
  }

  tambahCatatan(catatanBaru) {
    return this.withCatatan(catatanBaru)
  }

  toOrderItemPayload() {
    return {
      menu_id: this.#menu.id,
      jumlah: this.#jumlah,
      catatan: this.#catatan || null,
      condiment_ids: this.#condiments.map((c) => c.id),
    }
  }

  toJSON() {
    return {
      key: this.#key,
      menu: this.#menu.toJSON(),
      jumlah: this.#jumlah,
      condiments: this.#condiments.map((c) => c.toJSON()),
      catatan: this.#catatan,
    }
  }

  static fromJSON(raw) {
    return new CartItem(raw)
  }
}

export { CartItem as DetailPesanan }
