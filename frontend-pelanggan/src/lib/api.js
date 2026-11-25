import { cafeApiService } from '../services/CafeApiService.js'

// Re-export kompatibel menuju CafeApiService (OOP)
export const getMeja = (qrCode) => cafeApiService.getMeja(qrCode)
export const getKategoris = () => cafeApiService.getKategoris()
export const getMenus = (params) => cafeApiService.getMenus(params)
export const createPesanan = (payload) => cafeApiService.createPesanan(payload)
export const getPesanan = (nomor) => cafeApiService.getPesanan(nomor)
export const cekPembayaran = (nomor) => cafeApiService.cekPembayaran(nomor)
export { cafeApiService }
