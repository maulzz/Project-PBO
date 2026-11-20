<?php

namespace App\Filament\Widgets;

use App\Models\Menu;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PesananStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        /** @var array<string, int> $jumlahPerStatus */
        $jumlahPerStatus = Pesanan::query()
            ->selectRaw('status_pesanan, count(*) as total')
            ->groupBy('status_pesanan')
            ->pluck('total', 'status_pesanan')
            ->all();

        $pendapatanHariIni = (float) Pembayaran::query()
            ->where('status_pembayaran', 'lunas')
            ->whereDate('waktu_bayar', today())
            ->sum('total_bayar');

        $stokMenipis = Menu::where('stok', '<=', 5)->count();
        $pesananMasuk = $jumlahPerStatus['Pesanan Masuk'] ?? 0;

        return [
            Stat::make('Pesanan Baru Masuk', $pesananMasuk)
                ->description('Menunggu konfirmasi pelayan')
                ->descriptionIcon('heroicon-m-bell-alert')
                ->color($pesananMasuk > 0 ? 'warning' : 'gray'),

            Stat::make('Sedang Diproses', ($jumlahPerStatus['Diterima'] ?? 0) + ($jumlahPerStatus['Diproses'] ?? 0))
                ->description('Diterima & disiapkan di dapur/bar')
                ->descriptionIcon('heroicon-m-fire')
                ->color('primary'),

            Stat::make('Pendapatan Hari Ini', 'Rp '.number_format($pendapatanHariIni, 0, ',', '.'))
                ->description(($jumlahPerStatus['Selesai'] ?? 0).' pesanan selesai (total)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Menu Menipis / Habis', $stokMenipis)
                ->description('Stok ≤ 5 porsi')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($stokMenipis > 0 ? 'danger' : 'success'),
        ];
    }
}
