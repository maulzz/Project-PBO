<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\DetailPesanan;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPesanan extends EditRecord
{
    protected static string $resource = PesananResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('Lihat Detail'),
        ];
    }

    protected function afterSave(): void
    {
        $pesanan = $this->record->refresh();
        $pesanan->load('detailPesanans.condiments');

        $pesanan->detailPesanans->each(function (DetailPesanan $detail): void {
            $hargaCondiment = (float) $detail->condiments->sum('harga_condiment');
            $detail->update([
                'subtotal' => ((float) $detail->harga_satuan + $hargaCondiment) * $detail->jumlah,
            ]);
        });

        $pesanan->update([
            'total_harga' => $pesanan->detailPesanans()->sum('subtotal'),
        ]);
    }
}
