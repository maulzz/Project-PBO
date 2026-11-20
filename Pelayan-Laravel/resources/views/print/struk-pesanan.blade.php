<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $pesanan->nomor_pesanan }}</title>
    <style>
        body { font-family: 'Courier New', monospace; width: 300px; margin: 20px auto; color: #111; font-size: 12px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #333; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 2px 0; }
        .right { text-align: right; }
        .sub { font-size: 11px; color: #444; padding-left: 8px; }
        .actions { margin-top: 16px; display: flex; gap: 8px; justify-content: center; }
        .btn { padding: 8px 14px; border: 1px solid #ea580c; background: #ea580c; color: #fff; cursor: pointer; border-radius: 6px; font-family: sans-serif; font-size: 13px; text-decoration: none; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="center">
        <div class="bold" style="font-size: 16px;">CAFE AHLISURGA</div>
        <div>Struk Pembayaran Pelanggan</div>
    </div>

    <div class="line"></div>
    <table>
        <tr><td>No. Pesanan</td><td class="right bold">{{ $pesanan->nomor_pesanan }}</td></tr>
        <tr><td>Meja</td><td class="right bold">{{ $pesanan->meja?->nomor_meja ?? '-' }}</td></tr>
        <tr><td>Pelanggan</td><td class="right">{{ $pesanan->nama_pengunjung }}</td></tr>
        <tr><td>Tanggal</td><td class="right">{{ $pesanan->tanggal_pesanan?->format('d/m/Y H:i') }}</td></tr>
        <tr><td>Pelayan</td><td class="right">{{ $pesanan->pelayan?->name ?? '-' }}</td></tr>
    </table>
    <div class="line"></div>

    <table>
        @foreach ($pesanan->detailPesanans as $detail)
            <tr>
                <td colspan="2" class="bold">{{ $detail->menu?->nama_menu }}</td>
            </tr>
            <tr>
                <td>{{ $detail->jumlah }} x Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if ($detail->condiments->isNotEmpty())
                <tr>
                    <td colspan="2" class="sub">+ {{ $detail->condiments->pluck('nama_condiment')->implode(', ') }}</td>
                </tr>
            @endif
            @if ($detail->catatan)
                <tr>
                    <td colspan="2" class="sub">Catatan: {{ $detail->catatan }}</td>
                </tr>
            @endif
        @endforeach
    </table>

    <div class="line"></div>
    <table>
        <tr class="bold" style="font-size: 14px;">
            <td>TOTAL</td>
            <td class="right">Rp {{ number_format((float) $pesanan->total_harga, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Metode</td>
            <td class="right">{{ ucfirst($pesanan->pembayaran?->metode_pembayaran ?? 'Kasir') }}</td>
        </tr>
        <tr>
            <td>Status Bayar</td>
            <td class="right bold">{{ strtoupper($pesanan->pembayaran?->status_pembayaran ?? 'Menunggu') }}</td>
        </tr>
    </table>
    <div class="line"></div>

    <div class="center" style="margin-top: 8px;">
        Terima kasih atas kunjungan Anda!
    </div>

    <div class="actions">
        <button type="button" class="btn" onclick="window.print()">Cetak Struk</button>
    </div>
</body>
</html>
