import { BaseEntity } from './BaseEntity.js'

export class KategoriMenu extends BaseEntity {
  #namaKategori
  #deskripsi

  constructor({ id, nama_kategori, deskripsi = null }) {
    super(id)
    this.#namaKategori = nama_kategori ?? ''
    this.#deskripsi = deskripsi
  }

  get idKategori() {
    return this.id
  }

  get nama_kategori() {
    return this.#namaKategori
  }

  get namaKategori() {
    return this.#namaKategori
  }

  get deskripsi() {
    return this.#deskripsi
  }

  tampilkanKategori() {
    return {
      idKategori: this.id,
      namaKategori: this.#namaKategori,
      deskripsi: this.#deskripsi,
    }
  }

  static fromApi(data) {
    return new KategoriMenu(data)
  }

  toJSON() {
    return {
      id: this.id,
      nama_kategori: this.#namaKategori,
      deskripsi: this.#deskripsi,
    }
  }
}
