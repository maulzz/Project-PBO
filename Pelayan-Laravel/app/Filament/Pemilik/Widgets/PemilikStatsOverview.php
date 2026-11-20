<?php

namespace App\Filament\Pemilik\Widgets;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PemilikStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $totalPendapatan = (float) Pembayaran::where('status_pembayaran', 'lunas')->sum('total_bayar');
        $pendapatanHariIni = (float) Pembayaran::where('status_pembayaran', 'lunas')
            ->whereDate('waktu_bayar', today())
            ->sum('total_bayar');

        $jumlahPerStatus = Pesanan::query()
            ->selectRaw('status_pesanan, count(*) as total')
            ->groupBy('status_pesanan')
            ->pluck('total', 'status_pesanan')
            ->all();

        $totalTransaksi = array_sum($jumlahPerStatus);
        $dibatalkan = $jumlahPerStatus['Dibatalkan'] ?? 0;
        $stokMenipis = Menu::where('stok', '<=', 5)->count();

        return [
            Stat::make('Total Pendapatan (Lunas)', 'Rp '.number_format($totalPendapatan, 0, ',', '.'))
                ->description('Hari ini: Rp '.number_format($pendapatanHariIni, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Pesanan Tercatat', $totalTransaksi)
                ->description('Selesai: '.($jumlahPerStatus['Selesai'] ?? 0).' • Dibatalkan: '.$dibatalkan)
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Pesanan Sedang Berjalan', ($jumlahPerStatus['Pesanan Masuk'] ?? 0) + ($jumlahPerStatus['Diterima'] ?? 0) + ($jumlahPerStatus['Diproses'] ?? 0))
                ->description('Masuk, Diterima, dan Diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Menu Habis / Menipis', $stokMenipis)
                ->description('Stok ≤ 5 porsi perlu restock')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stokMenipis > 0 ? 'danger' : 'success'),
        ];
    }
}
