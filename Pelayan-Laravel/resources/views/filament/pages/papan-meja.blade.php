<x-filament-panels::page>
    <style>
        .pm-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }
        .pm-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 600;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            color: #111827;
        }
        .dark .pm-pill {
            background: #18181b;
            border-color: #27272a;
            color: #f4f4f5;
        }
        .pm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 16px;
        }
        .pm-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-radius: 14px;
            padding: 18px;
            background: #ffffff;
            border: 1.5px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .pm-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        .dark .pm-card {
            background: #18181b;
            border-color: #27272a;
        }
        .pm-card--tersedia {
            border-color: rgba(34, 197, 94, 0.45);
        }
        .dark .pm-card--tersedia {
            border-color: rgba(34, 197, 94, 0.4);
        }
        .pm-card--terisi {
            border-color: rgba(239, 68, 68, 0.65);
        }
        .dark .pm-card--terisi {
            border-color: rgba(239, 68, 68, 0.6);
        }
        .pm-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }
        .pm-title-wrap {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .pm-dot {
            width: 12px;
            height: 12px;
            border-radius: 9999px;
            flex-shrink: 0;
        }
        .pm-dot--hijau {
            background-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.18);
        }
        .pm-dot--merah {
            background-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.22);
        }
        .pm-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: #111827;
            line-height: 1.25;
        }
        .dark .pm-title {
            color: #ffffff;
        }
        .pm-sub {
            margin: 3px 0 0;
            font-size: 12px;
            color: #6b7280;
        }
        .dark .pm-sub {
            color: #a1a1aa;
        }
        .pm-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .pm-status-badge--tersedia {
            background: rgba(34, 197, 94, 0.14);
            color: #15803d;
        }
        .dark .pm-status-badge--tersedia {
            background: rgba(34, 197, 94, 0.18);
            color: #4ade80;
        }
        .pm-status-badge--terisi {
            background: rgba(239, 68, 68, 0.14);
            color: #b91c1c;
        }
        .dark .pm-status-badge--terisi {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        .pm-order-box {
            margin-top: 14px;
            padding: 12px;
            border-radius: 10px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            font-size: 12.5px;
        }
        .dark .pm-order-box {
            background: #27272a;
            border-color: #3f3f46;
        }
        .pm-order-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }
        .pm-order-no {
            font-weight: 700;
            color: #111827;
        }
        .dark .pm-order-no {
            color: #f4f4f5;
        }
        .pm-order-step {
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(249, 115, 22, 0.15);
            color: #c2410c;
        }
        .dark .pm-order-step {
            background: rgba(249, 115, 22, 0.22);
            color: #fb923c;
        }
        .pm-order-customer {
            margin-top: 6px;
            color: #4b5563;
        }
        .dark .pm-order-customer {
            color: #d4d4d8;
        }
        .pm-order-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #d1d5db;
        }
        .dark .pm-order-footer {
            border-top-color: #3f3f46;
        }
        .pm-order-total {
            font-weight: 700;
            font-size: 13.5px;
            color: #111827;
        }
        .dark .pm-order-total {
            color: #ffffff;
        }
        .pm-empty-box {
            margin-top: 14px;
            padding: 18px 12px;
            border-radius: 10px;
            border: 1px dashed #d1d5db;
            text-align: center;
            font-size: 12.5px;
            color: #9ca3af;
        }
        .dark .pm-empty-box {
            border-color: #3f3f46;
            color: #71717a;
        }
        .pm-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 14px;
        }
        .pm-btn-primary {
            flex: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            color: #ffffff;
            background: #ea580c;
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .pm-btn-primary:hover {
            background: #f97316;
        }
        .pm-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 600;
            cursor: pointer;
            background: #ffffff;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .dark .pm-btn-secondary {
            background: #27272a;
            color: #e4e4e7;
            border-color: #3f3f46;
        }
        .pm-btn-secondary:hover {
            background: #f3f4f6;
        }
        .dark .pm-btn-secondary:hover {
            background: #3f3f46;
        }
    </style>

    @php
        $mejas = $this->getMejas();
        $jumlahTerisi = $mejas->filter(fn ($m) => $m->status === 'terisi' || $m->pesanans->isNotEmpty())->count();
        $jumlahTersedia = $mejas->count() - $jumlahTerisi;
    @endphp

    <div wire:poll.15s>
        <div class="pm-summary">
            <div class="pm-pill">
                <span class="pm-dot pm-dot--hijau"></span>
                <span>Tersedia: {{ $jumlahTersedia }}</span>
            </div>
            <div class="pm-pill">
                <span class="pm-dot pm-dot--merah"></span>
                <span>Terisi: {{ $jumlahTerisi }}</span>
            </div>
        </div>

        <div class="pm-grid">
            @foreach ($mejas as $meja)
                @php
                    $pesananAktif = $meja->pesanans->first();
                    $isTerisi = $meja->status === 'terisi' || $pesananAktif !== null;
                @endphp

                <div class="pm-card {{ $isTerisi ? 'pm-card--terisi' : 'pm-card--tersedia' }}">
                    <div>
                        <div class="pm-header">
                            <div class="pm-title-wrap">
                                <span class="pm-dot {{ $isTerisi ? 'pm-dot--merah' : 'pm-dot--hijau' }}" title="{{ $isTerisi ? 'Terisi' : 'Tersedia' }}"></span>
                                <div>
                                    <h3 class="pm-title">Meja {{ $meja->nomor_meja }}</h3>
                                    <p class="pm-sub">Kapasitas: {{ $meja->kapasitas ?? '-' }} orang</p>
                                </div>
                            </div>

                            <span class="pm-status-badge {{ $isTerisi ? 'pm-status-badge--terisi' : 'pm-status-badge--tersedia' }}">
                                {{ $isTerisi ? 'Terisi' : 'Tersedia' }}
                            </span>
                        </div>

                        @if ($pesananAktif)
                            <div class="pm-order-box">
                                <div class="pm-order-row">
                                    <span class="pm-order-no">{{ $pesananAktif->nomor_pesanan }}</span>
                                    <span class="pm-order-step">{{ $pesananAktif->status_pesanan }}</span>
                                </div>

                                <div class="pm-order-customer">
                                    Pemesan: <strong>{{ $pesananAktif->nama_pengunjung }}</strong>
                                </div>

                                <div class="pm-order-footer">
                                    <span class="pm-order-total">
                                        Rp {{ number_format((float) $pesananAktif->total_harga, 0, ',', '.') }}
                                    </span>

                                    @if ($pesananAktif->pembayaran?->status_pembayaran === 'lunas')
                                        <span style="color: #22c55e; font-weight: 700; font-size: 12px;">Lunas</span>
                                    @else
                                        <span style="color: #ef4444; font-weight: 700; font-size: 12px;">Belum Lunas</span>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="pm-empty-box">
                                Siap digunakan • Tidak ada pesanan aktif
                            </div>
                        @endif
                    </div>

                    @if ($pesananAktif || $meja->status === 'terisi')
                        <div class="pm-actions">
                            @if ($pesananAktif)
                                <a
                                    href="{{ \App\Filament\Resources\Pesanans\PesananResource::getUrl('view', ['record' => $pesananAktif->id]) }}"
                                    class="pm-btn-primary"
                                >
                                    Lihat Pesanan
                                </a>
                            @endif

                            @if ($meja->status === 'terisi')
                                <button
                                    type="button"
                                    wire:click="kosongkanMeja({{ $meja->id }})"
                                    class="pm-btn-secondary"
                                >
                                    Kosongkan
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
