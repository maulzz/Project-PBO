<?php

namespace App\Filament\Widgets;

use App\Models\Pesanan;
use Carbon\CarbonPeriod;
use Filament\Widgets\ChartWidget;

class PendapatanChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pendapatan & Jumlah Pesanan';

    protected ?string $description = 'Dari pesanan yang tidak dibatalkan';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    protected ?string $pollingInterval = '60s';

    public ?string $filter = '7';

    protected function getFilters(): ?array
    {
        return [
            '7' => '7 hari terakhir',
            '14' => '14 hari terakhir',
            '30' => '30 hari terakhir',
        ];
    }

    protected function getData(): array
    {
        $jumlahHari = (int) ($this->filter ?? 7);
        $mulai = today()->subDays($jumlahHari - 1);

        $rekap = Pesanan::query()
            ->selectRaw('DATE(tanggal_pesanan) as tanggal, SUM(total_harga) as pendapatan, COUNT(*) as jumlah')
            ->where('status_pesanan', '!=', 'Dibatalkan')
            ->where('tanggal_pesanan', '>=', $mulai)
            ->groupByRaw('DATE(tanggal_pesanan)')
            ->get()
            ->keyBy(fn ($row): string => substr((string) $row->tanggal, 0, 10));

        $labels = [];
        $pendapatan = [];
        $jumlah = [];

        foreach (CarbonPeriod::create($mulai, today()) as $tanggal) {
            $key = $tanggal->toDateString();
            $labels[] = $tanggal->format('d M');
            $pendapatan[] = (float) ($rekap[$key]->pendapatan ?? 0);
            $jumlah[] = (int) ($rekap[$key]->jumlah ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $pendapatan,
                    'borderColor' => '#f97316',
                    'backgroundColor' => 'rgba(249, 115, 22, 0.15)',
                    'fill' => true,
                    'tension' => 0.35,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Jumlah Pesanan',
                    'data' => $jumlah,
                    'borderColor' => '#64748b',
                    'backgroundColor' => '#64748b',
                    'type' => 'bar',
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
