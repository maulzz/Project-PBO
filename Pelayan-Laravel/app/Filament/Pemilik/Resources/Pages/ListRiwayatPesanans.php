<?php

namespace App\Filament\Pemilik\Resources\Pages;

use App\Filament\Pemilik\Resources\RiwayatPesananResource;
use App\Models\Pesanan;
use App\Services\Export\SimpleXlsxExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListRiwayatPesanans extends ListRecords
{
    protected static string $resource = RiwayatPesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Export Riwayat Pesanan ke Excel (.xlsx)')
                ->modalDescription('Pilih rentang tanggal pesanan yang ingin diunduh.')
                ->modalSubmitActionLabel('Unduh Excel')
                ->schema([
                    DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal')
                        ->required()
                        ->default(now()->startOfMonth()),
                    DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal')
                        ->required()
                        ->default(now())
                        ->afterOrEqual('dari_tanggal'),
                ])
                ->action(function (array $data): BinaryFileResponse {
                    $dari = (string) $data['dari_tanggal'];
                    $sampai = (string) $data['sampai_tanggal'];

                    $pesanans = Pesanan::query()
                        ->with(['meja', 'pelayan', 'pembatal', 'pembayaran.pengonfirmasi'])
                        ->whereDate('tanggal_pesanan', '>=', $dari)
                        ->whereDate('tanggal_pesanan', '<=', $sampai)
                        ->orderBy('tanggal_pesanan')
                        ->get();

                    $headers = [
                        'No. Pesanan',
                        'Meja',
                        'Pengunjung',
                        'Status Pesanan',
                        'Total Harga',
                        'Metode Pembayaran',
                        'Status Pembayaran',
                        'Diterima Oleh',
                        'Konfirmasi Bayar Oleh',
                        'Dibatalkan Oleh',
                        'Waktu Pesanan',
                    ];

                    $rows = $pesanans->map(fn (Pesanan $p): array => [
                        $p->nomor_pesanan,
                        $p->meja?->nomor_meja ?? '-',
                        $p->nama_pengunjung,
                        $p->status_pesanan,
                        (float) $p->total_harga,
                        $p->pembayaran?->metode_pembayaran ?? '-',
                        $p->pembayaran?->status_pembayaran ?? '-',
                        $p->pelayan?->name ?? '-',
                        $p->pembayaran?->pengonfirmasi?->name ?? '-',
                        $p->pembatal?->name ?? '-',
                        $p->tanggal_pesanan?->format('Y-m-d H:i') ?? '-',
                    ])->all();

                    $filePath = SimpleXlsxExporter::create($headers, $rows, 'Riwayat Pesanan');

                    return response()
                        ->download($filePath, "riwayat-pesanan-{$dari}_sd_{$sampai}.xlsx")
                        ->deleteFileAfterSend(true);
                }),
        ];
    }
}
