<?php

namespace App\Filament\Resources\Pesanans\Tables;

use App\Contracts\MetodePembayaran;
use App\Models\Pelayan;
use App\Models\Pesanan;
use App\Services\Pembayaran\PembayaranKasir;
use App\Services\Pembayaran\PembayaranOnline;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Throwable;

class PesanansTable
{
    public static function formatRupiah(mixed $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['meja', 'pelayan', 'pembayaran']))
            ->defaultSort('created_at', 'desc')
            ->poll('15s')
            ->columns([
                TextColumn::make('nomor_pesanan')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Nomor pesanan disalin!'),
                TextColumn::make('meja.nomor_meja')
                    ->label('Meja')
                    ->sortable()
                    ->badge()
                    ->color('warning'),
                TextColumn::make('nama_pengunjung')
                    ->label('Pengunjung')
                    ->searchable()
                    ->sortable(),
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
                    ->formatStateUsing(fn ($state): string => self::formatRupiah($state))
                    ->sortable(),
                TextColumn::make('pembayaran.metode_pembayaran')
                    ->label('Metode')
                    ->formatStateUsing(fn (?string $state): string => $state === 'online' ? 'Online' : 'Kasir')
                    ->badge()
                    ->color('gray')
                    ->placeholder('-'),
                TextColumn::make('pembayaran.status_pembayaran')
                    ->label('Pembayaran')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'lunas' => 'success',
                        'menunggu' => 'warning',
                        'gagal', 'dibatalkan' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'Belum ada')
                    ->placeholder('Belum ada'),
                TextColumn::make('pelayan.name')
                    ->label('Pelayan')
                    ->placeholder('Belum ditugaskan')
                    ->toggleable(),
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
                ViewAction::make()
                    ->label('Detail')
                    ->button()
                    ->size('sm')
                    ->color('gray'),
                self::terimaAction(),
                self::prosesAction(),
                self::selesaiAction(),
                self::konfirmasiBayarAction(),
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('cetakStruk')
                        ->label('Cetak Struk')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Pesanan $record): string => route('pesanan.print.struk', $record))
                        ->openUrlInNewTab(),
                    Action::make('tiketDapur')
                        ->label('Tiket Dapur')
                        ->icon('heroicon-o-ticket')
                        ->url(fn (Pesanan $record): string => route('pesanan.print.tiket-dapur', $record))
                        ->openUrlInNewTab(),
                    self::batalkanAction(),
                ]),
            ]);
    }

    protected static function terimaAction(): Action
    {
        return Action::make('terima')
            ->label('Terima')
            ->icon('heroicon-o-check-circle')
            ->color('info')
            ->button()
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Terima Pesanan')
            ->modalDescription('Stok menu akan otomatis berkurang dan meja akan ditandai terisi.')
            ->visible(fn (Pesanan $record): bool => $record->status_pesanan === 'Pesanan Masuk')
            ->action(function (Pesanan $record): void {
                $pelayan = Pelayan::find(Auth::id());

                try {
                    $pelayan?->terimaPesanan($record);

                    Notification::make()->title('Pesanan diterima!')->success()->send();
                } catch (Throwable $e) {
                    Notification::make()
                        ->title('Gagal menerima pesanan')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function prosesAction(): Action
    {
        return Action::make('proses')
            ->label('Proses')
            ->icon('heroicon-o-fire')
            ->color('primary')
            ->button()
            ->size('sm')
            ->visible(fn (Pesanan $record): bool => $record->status_pesanan === 'Diterima')
            ->action(function (Pesanan $record): void {
                Pelayan::find(Auth::id())?->ubahStatusPesanan($record, 'Diproses');

                Notification::make()->title('Pesanan sedang diproses')->success()->send();
            });
    }

    protected static function selesaiAction(): Action
    {
        return Action::make('selesai')
            ->label('Selesai')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->button()
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading('Selesaikan Pesanan')
            ->modalDescription('Pesanan sudah diantar ke meja pengunjung?')
            ->visible(fn (Pesanan $record): bool => $record->status_pesanan === 'Diproses')
            ->action(function (Pesanan $record): void {
                Pelayan::find(Auth::id())?->ubahStatusPesanan($record, 'Selesai');

                Notification::make()->title('Pesanan selesai!')->success()->send();
            });
    }

    protected static function konfirmasiBayarAction(): Action
    {
        return Action::make('konfirmasiBayar')
            ->label('Bayar')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->button()
            ->size('sm')
            ->modalHeading('Konfirmasi Pembayaran di Kasir')
            ->modalDescription(fn (Pesanan $record): string => "Pesanan {$record->nomor_pesanan} — Total ".self::formatRupiah($record->total_harga))
            ->modalSubmitActionLabel('Tandai Lunas')
            ->visible(fn (Pesanan $record): bool => $record->status_pesanan !== 'Dibatalkan'
                && $record->pembayaran?->status_pembayaran !== 'lunas')
            ->fillForm(fn (Pesanan $record): array => [
                'metode_pembayaran' => $record->pembayaran?->metode_pembayaran ?? 'kasir',
                'total_bayar' => (float) $record->total_harga,
            ])
            ->schema([
                Select::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options([
                        'kasir' => 'Tunai di Kasir',
                        'online' => 'Online (QRIS / Transfer)',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('total_bayar')
                    ->label('Total Tagihan')
                    ->prefix('Rp')
                    ->numeric()
                    ->disabled()
                    ->dehydrated(),
                TextInput::make('uang_diterima')
                    ->label('Uang Diterima')
                    ->prefix('Rp')
                    ->numeric()
                    ->live(onBlur: true)
                    ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'kasir')
                    ->required(fn (Get $get): bool => $get('metode_pembayaran') === 'kasir')
                    ->minValue(fn (Get $get): float => (float) $get('total_bayar'))
                    ->validationMessages(['min' => 'Uang yang diterima kurang dari total tagihan.'])
                    ->afterStateUpdated(fn (Get $get, Set $set, $state) => $set(
                        'kembalian',
                        self::formatRupiah(max(0, (float) $state - (float) $get('total_bayar'))),
                    )),
                TextInput::make('kembalian')
                    ->label('Kembalian')
                    ->disabled()
                    ->dehydrated(false)
                    ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'kasir'),
                TextInput::make('transaksi_ref')
                    ->label('No. Referensi Transaksi')
                    ->visible(fn (Get $get): bool => $get('metode_pembayaran') === 'online'),
            ])
            ->action(function (Pesanan $record, array $data): void {
                /** @var MetodePembayaran $metode */
                $metode = $data['metode_pembayaran'] === 'online'
                    ? new PembayaranOnline($record, $data['transaksi_ref'] ?? null)
                    : new PembayaranKasir($record, (float) ($data['uang_diterima'] ?? 0));

                $metode->prosesPembayaran((float) $record->total_harga);

                $kembalian = $metode instanceof PembayaranKasir
                    ? ' Kembalian: '.self::formatRupiah($metode->getKembalian())
                    : '';

                Notification::make()
                    ->title('Pembayaran lunas')
                    ->body("Pesanan {$record->nomor_pesanan} telah dibayar.{$kembalian}")
                    ->success()
                    ->send();
            });
    }

    protected static function batalkanAction(): Action
    {
        return Action::make('batalkan')
            ->label('Batalkan')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Batalkan Pesanan')
            ->modalDescription('Pesanan yang dibatalkan tidak bisa dikembalikan. Lanjutkan?')
            ->visible(fn (Pesanan $record): bool => $record->isDapatDibatalkan())
            ->action(function (Pesanan $record): void {
                Pelayan::find(Auth::id())?->ubahStatusPesanan($record, 'Dibatalkan');

                Notification::make()->title('Pesanan dibatalkan')->warning()->send();
            });
    }
}
