<?php

namespace App\Filament\Pages;

use App\Models\Meja;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use UnitEnum;

class PapanMeja extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Cafe';

    protected static ?string $navigationLabel = 'Papan Meja';

    protected static ?string $title = 'Papan Status Meja';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.papan-meja';

    /**
     * @return Collection<int, Meja>
     */
    public function getMejas(): Collection
    {
        return Meja::query()
            ->with([
                'pesanans' => fn ($query) => $query
                    ->whereIn('status_pesanan', ['Pesanan Masuk', 'Diterima', 'Diproses'])
                    ->with('pembayaran')
                    ->latest('tanggal_pesanan'),
            ])
            ->orderBy('nomor_meja')
            ->get();
    }

    public function kosongkanMeja(int $mejaId): void
    {
        $meja = Meja::find($mejaId);

        if (! $meja) {
            return;
        }

        $meja->update(['status' => 'tersedia']);

        Notification::make()
            ->title("Meja {$meja->nomor_meja} dikosongkan")
            ->success()
            ->send();
    }
}
