<?php

namespace App\Filament\Pemilik\Resources;

use App\Filament\Pemilik\Resources\Pages\ListRiwayatPesanans;
use App\Filament\Pemilik\Resources\Pages\ViewRiwayatPesanan;
use App\Filament\Resources\Pesanans\RelationManagers\DetailPesanansRelationManager;
use App\Models\Pesanan;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class RiwayatPesananResource extends Resource
{
    protected static ?string $model = Pesanan::class;

    protected static ?string $slug = 'riwayat-pesanans';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Transaksi';

    protected static ?string $navigationLabel = 'Riwayat Pesanan';

    protected static ?string $modelLabel = 'Riwayat Pesanan';

    protected static ?string $pluralModelLabel = 'Riwayat Pesanan';

    protected static ?int $navigationSort = 1;

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
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
                            ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
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
                        TextEntry::make('catatan')
                            ->label('Catatan Pesanan')
                            ->placeholder('-'),
                    ]),
                Section::make('Pencatatan Pelaku Aksi (Audit Trail)')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('pelayan.name')
                            ->label('Diterima Oleh')
                            ->badge()
                            ->color('info')
                            ->placeholder('Belum diterima'),
                        TextEntry::make('pembayaran.pengonfirmasi.name')
                            ->label('Konfirmasi Bayar Oleh')
                            ->badge()
                            ->color('success')
                            ->placeholder('-'),
                        TextEntry::make('pembatal.name')
                            ->label('Dibatalkan Oleh')
                            ->badge()
                            ->color('danger')
                            ->placeholder('-'),
                        TextEntry::make('waktu_dibatalkan')
                            ->label('Waktu Dibatalkan')
                            ->dateTime('d M Y H:i')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with([
                'meja',
                'pelayan',
                'pembatal',
                'pembayaran.pengonfirmasi',
            ]))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('nomor_pesanan')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('meja.nomor_meja')
                    ->label('Meja')
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('nama_pengunjung')
                    ->label('Pengunjung')
                    ->searchable(),
                TextColumn::make('status_pesanan')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pesanan Masuk' => 'warning',
                        'Diterima' => 'info',
                        'Diproses' => 'primary',
                        'Selesai' => 'success',
                        'Dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('total_harga')
                    ->label('Total')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('pembayaran.status_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'lunas' => 'success',
                        'menunggu' => 'warning',
                        'gagal', 'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Belum ada'),
                TextColumn::make('pelayan.name')
                    ->label('Diterima Oleh')
                    ->placeholder('-'),
                TextColumn::make('pembayaran.pengonfirmasi.name')
                    ->label('Konfirmasi Bayar Oleh')
                    ->placeholder('-'),
                TextColumn::make('pembatal.name')
                    ->label('Dibatalkan Oleh')
                    ->badge()
                    ->color('danger')
                    ->placeholder('-'),
                TextColumn::make('tanggal_pesanan')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status_pesanan')
                    ->label('Status Pesanan')
                    ->options([
                        'Pesanan Masuk' => 'Pesanan Masuk',
                        'Diterima' => 'Diterima',
                        'Diproses' => 'Diproses',
                        'Selesai' => 'Selesai',
                        'Dibatalkan' => 'Dibatalkan',
                    ]),
                SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options([
                        'menunggu' => 'Menunggu',
                        'lunas' => 'Lunas',
                        'gagal' => 'Gagal',
                        'dibatalkan' => 'Dibatalkan',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $q, string $value): Builder => $q->whereHas('pembayaran', fn (Builder $p) => $p->where('status_pembayaran', $value)),
                    )),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DetailPesanansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRiwayatPesanans::route('/'),
            'view' => ViewRiwayatPesanan::route('/{record}'),
        ];
    }
}
