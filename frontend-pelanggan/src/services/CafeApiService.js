import { KategoriMenu } from '../models/KategoriMenu.js'
import { Meja } from '../models/Meja.js'
import { MenuItem } from '../models/MenuItem.js'
import { Pesanan } from '../models/Pesanan.js'
import { HttpClient } from './HttpClient.js'

const DEFAULT_API_BASE_URL = 'https://project-pbo.maulzzserver.my.id/api/v1/'

export class CafeApiService {
  #http

  constructor(httpClient) {
    this.#http = httpClient
  }

  get baseUrl() {
    return this.#http.baseUrl
  }

  async getMeja(qrCode) {
    const data = await this.#http.get(`/meja/${encodeURIComponent(qrCode)}`)
    return Meja.fromApi(data)
  }

  async getKategoris() {
    const data = await this.#http.get('/kategoris')
    return Array.isArray(data) ? data.map((item) => KategoriMenu.fromApi(item)) : []
  }

  async getMenus({ kategoriId = null, search = '' } = {}) {
    const data = await this.#http.get('/menus', {
      kategori_id: kategoriId,
      search: search.trim(),
    })
    return Array.isArray(data) ? data.map((item) => MenuItem.fromApi(item)) : []
  }

  async createPesanan(payload) {
    const data = await this.#http.post('/pesanans', payload)
    return Pesanan.fromApi(data)
  }

  async getPesanan(nomorPesanan) {
    const data = await this.#http.get(`/pesanans/${encodeURIComponent(nomorPesanan)}`)
    return Pesanan.fromApi(data)
  }

  async cekPembayaran(nomorPesanan) {
    const data = await this.#http.post(`/pesanans/${encodeURIComponent(nomorPesanan)}/cek-pembayaran`)
    return Pesanan.fromApi(data)
  }
}

const envApiUrl =
  typeof import.meta !== 'undefined' && import.meta.env
    ? import.meta.env.VITE_API_URL
    : undefined

export const cafeApiService = new CafeApiService(
  new HttpClient(envApiUrl ?? DEFAULT_API_BASE_URL),
)
