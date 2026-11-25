import { MetodePembayaranStrategy } from './MetodePembayaranStrategy.js'

const SANDBOX_SNAP_SCRIPT_URL = 'https://app.sandbox.midtrans.com/snap/snap.js'
const PRODUCTION_SNAP_SCRIPT_URL = 'https://app.midtrans.com/snap/snap.js'

export class PembayaranOnlineStrategy extends MetodePembayaranStrategy {
  #apiService
  #scriptLoader

  constructor(apiService, scriptLoader = null) {
    super()
    this.#apiService = apiService
    this.#scriptLoader = scriptLoader
  }

  getNamaMetode() {
    return 'online'
  }

  getLabel() {
    return 'Online (QRIS / e-Wallet)'
  }

  resolveSnapScriptUrl(snapUrl = '') {
    if (snapUrl && snapUrl.startsWith('https://app.midtrans.com')) {
      return PRODUCTION_SNAP_SCRIPT_URL
    }
    return SANDBOX_SNAP_SCRIPT_URL
  }

  async loadSnapScript(clientKey, snapUrl = '') {
    if (typeof window === 'undefined') return null
    if (window.snap?.pay) return window.snap

    if (this.#scriptLoader) {
      return this.#scriptLoader(clientKey, snapUrl)
    }

    const scriptSrc = this.resolveSnapScriptUrl(snapUrl)

    await new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${scriptSrc}"]`)
      if (existing) {
        if (window.snap?.pay) {
          resolve()
          return
        }
        existing.addEventListener('load', resolve, { once: true })
        existing.addEventListener('error', () => reject(new Error('Gagal memuat Midtrans Snap.')), { once: true })
        return
      }

      const script = document.createElement('script')
      script.src = scriptSrc
      if (clientKey) {
        script.setAttribute('data-client-key', clientKey)
      }
      script.async = true
      script.onload = resolve
      script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'))
      document.head.appendChild(script)
    })

    return window.snap ?? null
  }

  async prosesPembayaran(pesanan) {
    const pembayaran = pesanan?.pembayaran
    if (!pembayaran?.snapToken) {
      if (pembayaran?.snapUrl && typeof window !== 'undefined') {
        window.location.href = pembayaran.snapUrl
      }
      return { status: pembayaran?.statusPembayaran ?? 'menunggu', pesanan }
    }

    let snap = null
    try {
      snap = await this.loadSnapScript(pembayaran.clientKey, pembayaran.snapUrl ?? '')
    } catch {
      snap = null
    }

    if (!snap?.pay) {
      if (pembayaran.snapUrl && typeof window !== 'undefined') {
        window.location.href = pembayaran.snapUrl
      }
      return { status: 'redirected', pesanan }
    }

    return new Promise((resolve) => {
      snap.pay(pembayaran.snapToken, {
        onSuccess: async () => {
          const updated = await this.#apiService
            .cekPembayaran(pesanan.nomorPesanan)
            .catch(() => pesanan)
          resolve({ status: updated.pembayaran?.statusPembayaran ?? 'lunas', pesanan: updated })
        },
        onPending: async () => {
          const updated = await this.#apiService
            .cekPembayaran(pesanan.nomorPesanan)
            .catch(() => pesanan)
          resolve({ status: updated.pembayaran?.statusPembayaran ?? 'menunggu', pesanan: updated })
        },
        onError: async () => {
          const updated = await this.#apiService
            .cekPembayaran(pesanan.nomorPesanan)
            .catch(() => pesanan)
          resolve({ status: updated.pembayaran?.statusPembayaran ?? 'gagal', pesanan: updated })
        },
        onClose: () => {
          resolve({ status: 'closed', pesanan })
        },
      })
    })
  }
}

export { PembayaranOnlineStrategy as PembayaranOnline }
