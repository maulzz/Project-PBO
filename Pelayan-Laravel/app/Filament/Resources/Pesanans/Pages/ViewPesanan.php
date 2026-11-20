<?php

namespace App\Filament\Resources\Pesanans\Pages;

use App\Filament\Resources\Pesanans\PesananResource;
use App\Models\Pesanan;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPesanan extends ViewRecord
{
    protected static string $resource = PesananResource::class;

    protected static ?string $title = 'Detail Pesanan';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetakStruk')
                ->label('Cetak Struk')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn (Pesanan $record): string => route('pesanan.print.struk', $record))
                ->openUrlInNewTab(),
            Action::make('tiketDapur')
                ->label('Tiket Dapur')
                ->icon('heroicon-o-ticket')
                ->color('warning')
                ->url(fn (Pesanan $record): string => route('pesanan.print.tiket-dapur', $record))
                ->openUrlInNewTab(),
            EditAction::make()
                ->label('Edit Pesanan'),
        ];
    }
}
