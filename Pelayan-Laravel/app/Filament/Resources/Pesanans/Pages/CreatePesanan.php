<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\DetailPesanan;
use Filament\Resources\Pages\CreateRecord;

class CreatePesanan extends CreateRecord
{
    protected static string $resource = PesananResource::class;

    protected function afterCreate(): void
    {
        $this->sinkronkanTotalPesanan();
    }

    protected function sinkronkanTotalPesanan(): void
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
