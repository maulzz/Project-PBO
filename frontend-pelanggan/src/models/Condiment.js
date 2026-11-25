import { BaseEntity } from './BaseEntity.js'

export class Condiment extends BaseEntity {
  #namaCondiment
  #hargaCondiment
  #tersedia

  constructor({ id, nama_condiment, harga_condiment = 0, tersedia, status_ketersediaan }) {
    super(id)
    this.#namaCondiment = nama_condiment ?? ''
    this.#hargaCondiment = Number(harga_condiment ?? 0)
    const status = tersedia ?? status_ketersediaan ?? true
    this.#tersedia = Boolean(status)
  }

  get idCondiment() {
    return this.id
  }

  get nama_condiment() {
    return this.#namaCondiment
  }

  get namaCondiment() {
    return this.#namaCondiment
  }

  get harga_condiment() {
    return this.#hargaCondiment
  }

  get hargaCondiment() {
    return this.#hargaCondiment
  }

  get tersedia() {
    return this.#tersedia
  }

  get statusKetersediaan() {
    return this.#tersedia
  }

  get status_ketersediaan() {
    return this.#tersedia
  }

  cekKetersediaan() {
    return this.#tersedia
  }

  isGratis() {
    return this.#hargaCondiment <= 0
  }

  getFormattedHarga() {
    return this.isGratis() ? 'Gratis' : `+${this.formatRupiah(this.#hargaCondiment)}`
  }

  static fromApi(data) {
    return new Condiment(data)
  }

  toJSON() {
    return {
      id: this.id,
      nama_condiment: this.#namaCondiment,
      harga_condiment: this.#hargaCondiment,
      tersedia: this.#tersedia,
    }
  }
}
