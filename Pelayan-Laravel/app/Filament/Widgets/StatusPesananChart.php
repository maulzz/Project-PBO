<?php

namespace App\Filament\Widgets;

use App\Models\Pesanan;
use Filament\Widgets\ChartWidget;

class StatusPesananChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Status Pesanan Hari Ini';

    protected ?string $maxHeight = '260px';

    protected ?string $pollingInterval = '30s';

    /**
     * @var array<string, string>
     */
    protected const WARNA_STATUS = [
        'Pesanan Masuk' => '#f59e0b',
        'Diterima' => '#3b82f6',
        'Diproses' => '#f97316',
        'Selesai' => '#22c55e',
        'Dibatalkan' => '#ef4444',
    ];

    protected function getData(): array
    {
        $jumlahPerStatus = Pesanan::query()
            ->selectRaw('status_pesanan, count(*) as total')
            ->whereDate('tanggal_pesanan', today())
            ->groupBy('status_pesanan')
            ->pluck('total', 'status_pesanan');

        $statuses = array_keys(self::WARNA_STATUS);

        return [
            'datasets' => [
                [
                    'label' => 'Pesanan',
                    'data' => array_map(fn (string $status): int => (int) ($jumlahPerStatus[$status] ?? 0), $statuses),
                    'backgroundColor' => array_values(self::WARNA_STATUS),
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $statuses,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'x' => ['display' => false],
                'y' => ['display' => false],
            ],
            'plugins' => ['legend' => ['position' => 'bottom']],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
