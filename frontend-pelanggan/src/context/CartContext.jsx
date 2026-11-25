import { useEffect, useMemo, useState } from 'react'
import { ShoppingCart } from '../models/ShoppingCart.js'
import { CartContext } from './cartContextObject.js'

const STORAGE_KEY = 'cafe-cart-v1'

export function CartProvider({ children }) {
  const [cart, setCart] = useState(() => {
    try {
      return ShoppingCart.deserialize(sessionStorage.getItem(STORAGE_KEY))
    } catch {
      return new ShoppingCart()
    }
  })

  useEffect(() => {
    try {
      sessionStorage.setItem(STORAGE_KEY, cart.serialize())
    } catch {
      // Abaikan jika sessionStorage tidak tersedia
    }
  }, [cart])

  const value = useMemo(
    () => ({
      cart,
      meja: cart.meja,
      lines: cart.lines,
      totalItem: cart.getTotalItems(),
      totalHarga: cart.getTotalHarga(),
      hargaSatuan: (line) => line.getHargaSatuan(),

      setMeja: (mejaBaru) => setCart((prev) => prev.withMeja(mejaBaru)),
      tambah: (item) => setCart((prev) => prev.addItem(item)),
      ubahJumlah: (key, jumlah) => setCart((prev) => prev.updateQuantity(key, jumlah)),
      hapus: (key) => setCart((prev) => prev.removeItem(key)),
      kosongkan: () => setCart((prev) => prev.clear()),
    }),
    [cart],
  )

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}
