<?php

namespace App\Filament\Pemilik\Resources;

use App\Filament\Pemilik\Resources\Pages\ListRiwayatPembayarans;
use App\Models\Pembayaran;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RiwayatPembayaranResource extends Resource
{
    protected static ?string $model = Pembayaran::class;

    protected static ?string $slug = 'riwayat-pembayarans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Transaksi';

    protected static ?string $navigationLabel = 'Riwayat Pembayaran';

    protected static ?string $modelLabel = 'Riwayat Pembayaran';

    protected static ?string $pluralModelLabel = 'Riwayat Pembayaran';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['pesanan.meja', 'pengonfirmasi']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('pesanan.nomor_pesanan')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('pesanan.meja.nomor_meja')
                    ->label('Meja')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('pesanan.nama_pengunjung')
                    ->label('Pengunjung')
                    ->searchable(),
                TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->formatStateUsing(fn (string $state): string => $state === 'online' ? 'Online' : 'Kasir')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('total_bayar')
                    ->label('Total Bayar')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'lunas' => 'success',
                        'menunggu' => 'warning',
                        'gagal', 'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('transaksi_ref')
                    ->label('Referensi')
                    ->placeholder('-'),
                TextColumn::make('pengonfirmasi.name')
                    ->label('Dikonfirmasi Oleh')
                    ->badge()
                    ->color('info')
                    ->placeholder('-'),
                TextColumn::make('waktu_bayar')
                    ->label('Waktu Bayar')
                    ->dateTime('d M Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('metode_pembayaran')
                    ->label('Metode')
                    ->options([
                        'kasir' => 'Kasir',
                        'online' => 'Online',
                    ]),
                SelectFilter::make('status_pembayaran')
                    ->label('Status')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'lunas' => 'Lunas',
                        'gagal' => 'Gagal',
                        'dibatalkan' => 'Dibatalkan',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiwayatPembayarans::route('/'),
        ];
    }
}
