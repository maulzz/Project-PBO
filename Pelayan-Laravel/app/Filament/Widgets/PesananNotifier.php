<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\Pesanan;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

/**
 * Komponen tak terlihat yang dipasang di setiap halaman panel untuk
 * memantau pesanan baru, lalu memicu notifikasi layar & suara.
 */
class PesananNotifier extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.pesanan-notifier';

    protected const SESSION_KEY = 'pesanan_notifier.last_id';

    public int $lastPesananId = 0;

    public function mount(): void
    {
        $this->lastPesananId = (int) session(self::SESSION_KEY, Pesanan::max('id') ?? 0);
        session([self::SESSION_KEY => $this->lastPesananId]);
    }

    public function cekPesananBaru(): void
    {
        $pesananBaru = Pesanan::query()
            ->with('meja:id,nomor_meja')
            ->where('id', '>', $this->lastPesananId)
            ->where('status_pesanan', 'Pesanan Masuk')
            ->orderBy('id')
            ->get(['id', 'nomor_pesanan', 'meja_id', 'nama_pengunjung', 'total_harga']);

        if ($pesananBaru->isEmpty()) {
            return;
        }

        $this->lastPesananId = (int) $pesananBaru->max('id');
        session([self::SESSION_KEY => $this->lastPesananId]);
        Cache::forget('pesanan:jumlah-masuk');

        foreach ($pesananBaru as $pesanan) {
            Notification::make()
                ->title("Pesanan baru — Meja {$pesanan->meja?->nomor_meja}")
                ->body("{$pesanan->nama_pengunjung} • {$pesanan->nomor_pesanan} • Rp ".number_format((float) $pesanan->total_harga, 0, ',', '.'))
                ->icon('heroicon-o-bell-alert')
                ->iconColor('warning')
                ->persistent()
                ->actions([
                    Action::make('lihat')
                        ->label('Lihat Pesanan')
                        ->button()
                        ->url(PesananResource::getUrl('view', ['record' => $pesanan->id])),
                ])
                ->send();
        }

        $pertama = $pesananBaru->first();

        $this->dispatch(
            'pesanan-baru',
            jumlah: $pesananBaru->count(),
            judul: $pesananBaru->count() > 1
                ? "{$pesananBaru->count()} pesanan baru masuk"
                : "Pesanan baru — Meja {$pertama->meja?->nomor_meja}",
            pesan: "{$pertama->nama_pengunjung} • {$pertama->nomor_pesanan}",
            url: PesananResource::getUrl('index'),
        );
    }
}
