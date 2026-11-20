<?php

namespace App\Filament\Pemilik\Resources\Pages;

use App\Filament\Pemilik\Resources\RiwayatPesananResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRiwayatPesanan extends ViewRecord
{
    protected static string $resource = RiwayatPesananResource::class;

    protected static ?string $title = 'Detail Riwayat Pesanan';
}
