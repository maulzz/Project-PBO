import { Routes, Route, Navigate, useLocation } from 'react-router-dom'
import OrderPage from './pages/OrderPage.jsx'
import CartPage from './pages/CartPage.jsx'
import StatusPage from './pages/StatusPage.jsx'

function RedirectToOrderWithSearch() {
  const location = useLocation()
  return <Navigate to={`/order${location.search || ''}`} replace />
}

export default function App() {
  return (
    <div className="mx-auto min-h-screen max-w-md bg-stone-50 text-stone-900 shadow-sm">
      <Routes>
        {/* URL resmi di QR Code Backend: /order?meja=<qr_code> */}
        <Route path="/" element={<RedirectToOrderWithSearch />} />
        <Route path="/order" element={<OrderPage />} />
        <Route path="/order/:qrCode" element={<OrderPage />} />
        <Route path="/meja/:qrCode" element={<OrderPage />} />
        <Route path="/keranjang" element={<CartPage />} />
        <Route path="/pesanan/:nomor" element={<StatusPage />} />
        <Route path="*" element={<RedirectToOrderWithSearch />} />
      </Routes>
    </div>
  )
}
