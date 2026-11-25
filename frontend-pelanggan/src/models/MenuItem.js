import { BaseEntity } from './BaseEntity.js'
import { Condiment } from './Condiment.js'

export class MenuItem extends BaseEntity {
  #namaMenu
  #deskripsi
  #harga
  #gambarUrl
  #stokHabis
  #bisaDipesan
  #statusTeks
  #kategori
  #condiments

  constructor({
    id,
    nama_menu,
    deskripsi = null,
    harga = 0,
    gambar_url = null,
    stok_habis = false,
    bisa_dipesan = true,
    status_teks = 'Tersedia',
    kategori = null,
    condiments = [],
  }) {
    super(id)
    this.#namaMenu = nama_menu ?? ''
    this.#deskripsi = deskripsi
    this.#harga = Number(harga ?? 0)
    this.#gambarUrl = gambar_url
    this.#stokHabis = Boolean(stok_habis)
    this.#bisaDipesan = Boolean(bisa_dipesan)
    this.#statusTeks = status_teks ?? (this.#stokHabis ? 'Stok Habis' : 'Tersedia')
    this.#kategori = kategori
    this.#condiments = condiments.map((c) => (c instanceof Condiment ? c : Condiment.fromApi(c)))
  }

  get nama_menu() {
    return this.#namaMenu
  }

  get namaMenu() {
    return this.#namaMenu
  }

  get deskripsi() {
    return this.#deskripsi
  }

  get harga() {
    return this.#harga
  }

  get gambar_url() {
    return this.#gambarUrl
  }

  get gambarUrl() {
    return this.#gambarUrl
  }

  get stok_habis() {
    return this.#stokHabis
  }

  get stokHabis() {
    return this.#stokHabis
  }

  get bisa_dipesan() {
    return this.#bisaDipesan
  }

  get bisaDipesan() {
    return this.#bisaDipesan
  }

  get status_teks() {
    return this.#statusTeks
  }

  get statusTeks() {
    return this.#statusTeks
  }

  get kategori() {
    return this.#kategori
  }

  get condiments() {
    return [...this.#condiments]
  }

  isTersedia() {
    return this.#bisaDipesan && !this.#stokHabis
  }

  hasCondiments() {
    return this.#condiments.length > 0
  }

  getFormattedHarga() {
    return this.formatRupiah(this.#harga)
  }

  matchesFilter(kategoriId = null, search = '') {
    const cocokKategori = !kategoriId || this.#kategori?.id === kategoriId
    const kataKunci = search.trim().toLowerCase()
    const cocokNama = !kataKunci || this.#namaMenu.toLowerCase().includes(kataKunci)
    return cocokKategori && cocokNama
  }

  static fromApi(data) {
    return new MenuItem(data)
  }

  toJSON() {
    return {
      id: this.id,
      nama_menu: this.#namaMenu,
      deskripsi: this.#deskripsi,
      harga: this.#harga,
      gambar_url: this.#gambarUrl,
      stok_habis: this.#stokHabis,
      bisa_dipesan: this.#bisaDipesan,
      status_teks: this.#statusTeks,
      kategori: this.#kategori,
      condiments: this.#condiments.map((c) => c.toJSON()),
    }
  }
}
