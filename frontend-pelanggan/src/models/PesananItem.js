import { BaseEntity } from './BaseEntity.js'

export class PesananItem extends BaseEntity {
  #menuId
  #namaMenu
  #hargaSatuan
  #jumlah
  #catatan
  #subtotal
  #condiments

  constructor({
    id,
    menu_id,
    nama_menu = '',
    harga_satuan = 0,
    jumlah = 1,
    catatan = null,
    subtotal = 0,
    condiments = [],
  }) {
    super(id)
    this.#menuId = menu_id
    this.#namaMenu = nama_menu
    this.#hargaSatuan = Number(harga_satuan ?? 0)
    this.#jumlah = Number(jumlah ?? 1)
    this.#catatan = catatan
    this.#subtotal = Number(subtotal ?? 0)
    this.#condiments = condiments.map((c) => ({
      id: c.id,
      nama_condiment: c.nama_condiment,
      harga_condiment: Number(c.harga_condiment ?? 0),
    }))
  }

  get menuId() {
    return this.#menuId
  }

  get nama_menu() {
    return this.#namaMenu
  }

  get namaMenu() {
    return this.#namaMenu
  }

  get harga_satuan() {
    return this.#hargaSatuan
  }

  get jumlah() {
    return this.#jumlah
  }

  get catatan() {
    return this.#catatan
  }

  get subtotal() {
    return this.#subtotal
  }

  get condiments() {
    return [...this.#condiments]
  }

  getCondimentSummary() {
    return this.#condiments.map((c) => c.nama_condiment).join(', ')
  }

  static fromApi(data) {
    return new PesananItem(data)
  }

  toJSON() {
    return {
      id: this.id,
      menu_id: this.#menuId,
      nama_menu: this.#namaMenu,
      harga_satuan: this.#hargaSatuan,
      jumlah: this.#jumlah,
      catatan: this.#catatan,
      subtotal: this.#subtotal,
      condiments: this.#condiments,
    }
  }
}
