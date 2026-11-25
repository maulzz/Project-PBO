import { useEffect, useMemo, useState } from 'react'
import { Pesanan } from '../models/Pesanan.js'
import { Pengunjung } from '../models/Pengunjung.js'
import { ShoppingCart } from '../models/ShoppingCart.js'
import { CartContext } from './cartContextObject.js'

const STORAGE_KEY = 'cafe-cart-v1'
const CUSTOMER_STORAGE_KEY = 'cafe-customer-v1'

function loadCustomerState() {
  try {
    const raw = localStorage.getItem(CUSTOMER_STORAGE_KEY) ?? sessionStorage.getItem(CUSTOMER_STORAGE_KEY)
    if (!raw) return { namaPengunjung: '', riwayatPesanan: [] }
    const parsed = JSON.parse(raw)
    return {
      namaPengunjung: typeof parsed?.namaPengunjung === 'string' ? parsed.namaPengunjung : '',
      riwayatPesanan: Array.isArray(parsed?.riwayatPesanan)
        ? parsed.riwayatPesanan.map((item) => Pesanan.fromApi(item))
        : [],
    }
  } catch {
    return { namaPengunjung: '', riwayatPesanan: [] }
  }
}

export function CartProvider({ children }) {
  const [cart, setCart] = useState(() => {
    try {
      const saved = sessionStorage.getItem(STORAGE_KEY) ?? localStorage.getItem(STORAGE_KEY)
      return ShoppingCart.deserialize(saved)
    } catch {
      return new ShoppingCart()
    }
  })

  const [customerState, setCustomerState] = useState(loadCustomerState)

  useEffect(() => {
    try {
      const serialized = cart.serialize()
      sessionStorage.setItem(STORAGE_KEY, serialized)
      localStorage.setItem(STORAGE_KEY, serialized)
    } catch {
      // Abaikan jika storage tidak tersedia
    }
  }, [cart])

  useEffect(() => {
    try {
      const serialized = JSON.stringify({
        namaPengunjung: customerState.namaPengunjung,
        riwayatPesanan: customerState.riwayatPesanan.map((p) => p.toJSON()),
      })
      sessionStorage.setItem(CUSTOMER_STORAGE_KEY, serialized)
      localStorage.setItem(CUSTOMER_STORAGE_KEY, serialized)
    } catch {
      // Abaikan jika storage tidak tersedia
    }
  }, [customerState])

  const pengunjung = useMemo(
    () =>
      new Pengunjung({
        namaPengunjung: customerState.namaPengunjung,
        keranjang: cart,
        riwayatPesanan: customerState.riwayatPesanan,
      }),
    [customerState.namaPengunjung, customerState.riwayatPesanan, cart],
  )

  const value = useMemo(
    () => ({
      cart,
      pengunjung,
      meja: cart.meja,
      lines: cart.lines,
      totalItem: cart.getTotalItems(),
      totalHarga: cart.getTotalHarga(),
      hargaSatuan: (line) => line.getHargaSatuan(),
      namaPengunjung: customerState.namaPengunjung,
      riwayatPesanan: customerState.riwayatPesanan,

      setMeja: (mejaBaru) => setCart((prev) => prev.withMeja(mejaBaru)),
      clearMeja: () => setCart((prev) => prev.withMeja(null)),
      tambah: (item) => setCart((prev) => prev.addItem(item)),
      ubahJumlah: (key, jumlah) => setCart((prev) => prev.updateQuantity(key, jumlah)),
      ubahCatatan: (key, catatan) => setCart((prev) => prev.updateNote(key, catatan)),
      hapus: (key) => setCart((prev) => prev.removeItem(key)),
      kosongkan: () => setCart((prev) => prev.clear()),
      setNamaPengunjung: (nama) =>
        setCustomerState((prev) => ({ ...prev, namaPengunjung: String(nama ?? '') })),
      catatPesanan: (pesananBaru) => {
        if (!pesananBaru) return
        const model = pesananBaru instanceof Pesanan ? pesananBaru : Pesanan.fromApi(pesananBaru)
        setCustomerState((prev) => {
          const tanpaDuplikat = prev.riwayatPesanan.filter(
            (p) => p.nomorPesanan !== model.nomorPesanan,
          )
          return {
            namaPengunjung: model.namaPengunjung || prev.namaPengunjung,
            riwayatPesanan: [model, ...tanpaDuplikat].slice(0, 10),
          }
        })
      },
    }),
    [cart, pengunjung, customerState],
  )

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
