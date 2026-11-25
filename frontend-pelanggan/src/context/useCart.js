import { useContext } from 'react'
import { CartContext } from './cartContextObject.js'

export function useCart() {
  const ctx = useContext(CartContext)
  if (!ctx) {
    throw new Error('useCart harus digunakan di dalam CartProvider.')
  }
  return ctx
}
