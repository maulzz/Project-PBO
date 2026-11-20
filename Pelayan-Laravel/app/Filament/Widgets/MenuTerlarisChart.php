<?php

namespace App\Filament\Widgets;

use App\Models\DetailPesanan;
use Filament\Widgets\ChartWidget;

class MenuTerlarisChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Menu Terlaris';

    protected ?string $description = 'Top 5 menu berdasarkan jumlah porsi terjual';

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = '60s';

    protected function getData(): array
    {
        $terlaris = DetailPesanan::query()
            ->join('menus', 'menus.id', '=', 'detail_pesanans.menu_id')
            ->join('pesanans', 'pesanans.id', '=', 'detail_pesanans.pesanan_id')
            ->where('pesanans.status_pesanan', '!=', 'Dibatalkan')
            ->selectRaw('menus.nama_menu, SUM(detail_pesanans.jumlah) as total_terjual')
            ->groupBy('menus.nama_menu')
            ->orderByDesc('total_terjual')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Porsi terjual',
                    'data' => $terlaris->pluck('total_terjual')->map(fn ($total): int => (int) $total)->all(),
                    'backgroundColor' => ['#ea580c', '#f97316', '#fb923c', '#fdba74', '#fed7aa'],
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $terlaris->pluck('nama_menu')->all(),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
