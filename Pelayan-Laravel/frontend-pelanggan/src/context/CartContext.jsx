import { createContext, useContext, useEffect, useMemo, useState } from 'react'

const CartContext = createContext(null)
const STORAGE_KEY = 'cafe-cart-v1'

const hargaSatuan = (line) =>
  line.menu.harga + line.condiments.reduce((n, c) => n + c.harga_condiment, 0)

// Dua baris dianggap sama jika menu, condiment, dan catatannya sama.
const lineKey = (menuId, condiments, catatan) =>
  [menuId, condiments.map((c) => c.id).sort().join('.'), catatan.trim()].join('|')

export function CartProvider({ children }) {
  const [state, setState] = useState(() => {
    try {
      return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) ?? { meja: null, lines: [] }
    } catch {
      return { meja: null, lines: [] }
    }
  })

  useEffect(() => {
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state)) } catch { /* abaikan */ }
  }, [state])

  const value = useMemo(() => {
    const { meja, lines } = state
    return {
      meja,
      lines,
      totalItem: lines.reduce((n, l) => n + l.jumlah, 0),
      totalHarga: lines.reduce((n, l) => n + hargaSatuan(l) * l.jumlah, 0),
      hargaSatuan,

      setMeja: (m) =>
        setState((s) => (s.meja?.id === m.id ? { ...s, meja: m } : { meja: m, lines: [] })),

      tambah: ({ menu, jumlah, condiments, catatan }) =>
        setState((s) => {
          const key = lineKey(menu.id, condiments, catatan)
          const ada = s.lines.find((l) => l.key === key)
          const lines = ada
            ? s.lines.map((l) => (l.key === key ? { ...l, jumlah: Math.min(99, l.jumlah + jumlah) } : l))
            : [...s.lines, { key, menu, jumlah, condiments, catatan: catatan.trim() }]
          return { ...s, lines }
        }),

      ubahJumlah: (key, jumlah) =>
        setState((s) => ({
          ...s,
          lines: s.lines
            .map((l) => (l.key === key ? { ...l, jumlah: Math.min(99, jumlah) } : l))
            .filter((l) => l.jumlah > 0), // jumlah 0 = hapus dari keranjang
        })),

      hapus: (key) => setState((s) => ({ ...s, lines: s.lines.filter((l) => l.key !== key) })),
      kosongkan: () => setState((s) => ({ ...s, lines: [] })),
    }
  }, [state])

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>
}

export const useCart = () => useContext(CartContext)
