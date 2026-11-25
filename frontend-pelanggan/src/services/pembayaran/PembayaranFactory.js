import { cafeApiService } from '../CafeApiService.js'
import { PembayaranKasirStrategy } from './PembayaranKasirStrategy.js'
import { PembayaranOnlineStrategy } from './PembayaranOnlineStrategy.js'

export class PembayaranFactory {
  static create(metodePembayaran, apiService = cafeApiService, scriptLoader = null) {
    if (metodePembayaran === 'online') {
      return new PembayaranOnlineStrategy(apiService, scriptLoader)
    }
    return new PembayaranKasirStrategy()
  }
}
