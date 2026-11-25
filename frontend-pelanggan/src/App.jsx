import { Routes, Route, Navigate } from 'react-router-dom'
import OrderPage from './pages/OrderPage.jsx'
import CartPage from './pages/CartPage.jsx'
import StatusPage from './pages/StatusPage.jsx'

export default function App() {
  return (
    <div className="mx-auto min-h-screen max-w-md bg-stone-50 text-stone-900 shadow-sm">
      <Routes>
        {/* URL di QR Code: /order?meja=<qr_code> */}
        <Route path="/order" element={<OrderPage />} />
        <Route path="/keranjang" element={<CartPage />} />
        <Route path="/pesanan/:nomor" element={<StatusPage />} />
        <Route path="*" element={<Navigate to="/order" replace />} />
      </Routes>
    </div>
  )
}
