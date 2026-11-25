import { BaseEntity } from './BaseEntity.js'

export class Meja extends BaseEntity {
  #nomorMeja
  #qrCode
  #status
  #kapasitas

  constructor({ id, nomor_meja, qr_code, status = 'tersedia', kapasitas = null }) {
    super(id)
    this.#nomorMeja = nomor_meja ?? ''
    this.#qrCode = qr_code ?? ''
    this.#status = status
    this.#kapasitas = kapasitas
  }

  get nomor_meja() {
    return this.#nomorMeja
  }

  get nomorMeja() {
    return this.#nomorMeja
  }

  get qrCode() {
    return this.#qrCode
  }

  get status() {
    return this.#status
  }

  get kapasitas() {
    return this.#kapasitas
  }

  isTersedia() {
    return this.#status === 'tersedia'
  }

  getLabelMeja() {
    return `Meja ${this.#nomorMeja}`
  }

  static fromApi(data) {
    if (!data) return null
    return new Meja(data)
  }

  toJSON() {
    return {
      id: this.id,
      nomor_meja: this.#nomorMeja,
      qr_code: this.#qrCode,
      status: this.#status,
      kapasitas: this.#kapasitas,
    }
  }
}
