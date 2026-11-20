<?php

namespace App\Filament\Resources\Pesanans\Schemas;

use App\Filament\Resources\Pesanans\Tables\PesanansTable;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PesananInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pesanan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('nomor_pesanan')
                            ->label('Nomor Pesanan')
                            ->weight('bold')
                            ->copyable(),
                        TextEntry::make('meja.nomor_meja')
                            ->label('Meja')
                            ->badge()
                            ->color('warning'),
                        TextEntry::make('nama_pengunjung')
                            ->label('Nama Pengunjung'),
                        TextEntry::make('status_pesanan')
                            ->label('Status Pesanan')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'Pesanan Masuk' => 'warning',
                                'Diterima' => 'info',
                                'Diproses' => 'primary',
                                'Selesai' => 'success',
                                'Dibatalkan' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('total_harga')
                            ->label('Total Harga')
                            ->weight('bold')
                            ->formatStateUsing(fn ($state): string => PesanansTable::formatRupiah($state)),
                        TextEntry::make('tanggal_pesanan')
                            ->label('Waktu Pesanan')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('pembayaran.metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'online' => 'Online',
                                'kasir' => 'Kasir',
                                default => '-',
                            })
                            ->badge()
                            ->color('gray'),
                        TextEntry::make('pembayaran.status_pembayaran')
                            ->label('Status Pembayaran')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'lunas' => 'success',
                                'menunggu' => 'warning',
                                'gagal', 'dibatalkan' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Belum ada'),
                        TextEntry::make('pelayan.name')
                            ->label('Pelayan Penanggung Jawab')
                            ->placeholder('Belum ditugaskan'),
                        TextEntry::make('catatan')
                            ->label('Catatan Khusus Pesanan')
                            ->placeholder('Tidak ada catatan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
