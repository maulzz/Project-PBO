<?php

namespace App\Filament\Pemilik\Resources\Pages;

use App\Filament\Pemilik\Resources\RiwayatPembayaranResource;
use App\Models\Pembayaran;
use App\Services\Export\SimpleXlsxExporter;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ListRecords;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ListRiwayatPembayarans extends ListRecords
{
    protected static string $resource = RiwayatPembayaranResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Export Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->modalHeading('Export Riwayat Pembayaran ke Excel (.xlsx)')
                ->modalDescription('Pilih rentang tanggal pembayaran yang ingin diunduh.')
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

                    $pembayarans = Pembayaran::query()
                        ->with(['pesanan.meja', 'pengonfirmasi'])
                        ->whereRaw('DATE(COALESCE(waktu_bayar, created_at)) >= ?', [$dari])
                        ->whereRaw('DATE(COALESCE(waktu_bayar, created_at)) <= ?', [$sampai])
                        ->orderByRaw('COALESCE(waktu_bayar, created_at) ASC')
                        ->get();

                    $headers = [
                        'No. Pesanan',
                        'Meja',
                        'Pengunjung',
                        'Metode',
                        'Total Bayar',
                        'Status Pembayaran',
                        'Referensi',
                        'Dikonfirmasi Oleh',
                        'Waktu Bayar',
                    ];

                    $rows = $pembayarans->map(fn (Pembayaran $p): array => [
                        $p->pesanan?->nomor_pesanan ?? '-',
                        $p->pesanan?->meja?->nomor_meja ?? '-',
                        $p->pesanan?->nama_pengunjung ?? '-',
                        $p->metode_pembayaran === 'online' ? 'Online' : 'Kasir',
                        (float) $p->total_bayar,
                        ucfirst($p->status_pembayaran),
                        $p->transaksi_ref ?? '-',
                        $p->pengonfirmasi?->name ?? '-',
                        ($p->waktu_bayar ?? $p->created_at)?->format('Y-m-d H:i') ?? '-',
                    ])->all();

                    $filePath = SimpleXlsxExporter::create($headers, $rows, 'Riwayat Pembayaran');

                    return response()
                        ->download($filePath, "riwayat-pembayaran-{$dari}_sd_{$sampai}.xlsx")
                        ->deleteFileAfterSend(true);
                }),
        ];
    }
}
