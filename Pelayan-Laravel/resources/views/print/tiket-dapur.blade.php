<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tiket Dapur {{ $pesanan->nomor_pesanan }}</title>
    <style>
        body { font-family: 'Courier New', monospace; width: 300px; margin: 20px auto; color: #111; font-size: 13px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .meja-box { border: 2px solid #111; padding: 6px; margin: 8px 0; font-size: 20px; font-weight: bold; text-align: center; }
        .line { border-top: 1px dashed #333; margin: 8px 0; }
        .item { margin-bottom: 8px; }
        .sub { font-size: 12px; padding-left: 12px; }
        .note { font-size: 12px; padding-left: 12px; font-style: italic; }
        .actions { margin-top: 16px; text-align: center; }
        .btn { padding: 8px 14px; border: 1px solid #ea580c; background: #ea580c; color: #fff; cursor: pointer; border-radius: 6px; font-family: sans-serif; font-size: 13px; }
        @media print { .actions { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="center bold" style="font-size: 15px;">TIKET PESANAN DAPUR / BAR</div>
    <div class="meja-box">MEJA {{ $pesanan->meja?->nomor_meja ?? '-' }}</div>
    <div>No: <span class="bold">{{ $pesanan->nomor_pesanan }}</span></div>
    <div>Pemesan: {{ $pesanan->nama_pengunjung }}</div>
    <div>Waktu: {{ $pesanan->tanggal_pesanan?->format('d/m/Y H:i') }}</div>

    <div class="line"></div>
    @foreach ($pesanan->detailPesanans as $detail)
        <div class="item">
            <div class="bold">{{ $detail->jumlah }}x {{ $detail->menu?->nama_menu }}</div>
            @if ($detail->condiments->isNotEmpty())
                <div class="sub">+ {{ $detail->condiments->pluck('nama_condiment')->implode(', ') }}</div>
            @endif
            @if ($detail->catatan)
                <div class="note">* {{ $detail->catatan }}</div>
            @endif
        </div>
    @endforeach
    <div class="line"></div>

    @if ($pesanan->catatan)
        <div><span class="bold">Catatan Umum:</span> {{ $pesanan->catatan }}</div>
        <div class="line"></div>
    @endif

    <div class="actions">
        <button type="button" class="btn" onclick="window.print()">Cetak Tiket Dapur</button>
    </div>
</body>
</html>
