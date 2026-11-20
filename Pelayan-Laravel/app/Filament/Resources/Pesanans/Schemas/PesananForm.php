<?php

namespace App\Filament\Resources\Pesanans\Schemas;

use App\Models\Condiment;
use App\Models\Menu;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PesananForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pesanan')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nomor_pesanan')
                            ->label('Nomor Pesanan')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn (): string => 'ORD-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -5)))
                            ->disabled(fn (?string $operation): bool => $operation === 'edit')
                            ->dehydrated(),
                        Select::make('meja_id')
                            ->label('Meja')
                            ->relationship('meja', 'nomor_meja')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('nama_pengunjung')
                            ->label('Nama Pengunjung')
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('tanggal_pesanan')
                            ->label('Tanggal Pesanan')
                            ->required()
                            ->default(now()),
                        Select::make('status_pesanan')
                            ->label('Status Pesanan')
                            ->options([
                                'Pesanan Masuk' => 'Pesanan Masuk',
                                'Diterima' => 'Diterima',
                                'Diproses' => 'Diproses',
                                'Selesai' => 'Selesai',
                                'Dibatalkan' => 'Dibatalkan',
                            ])
                            ->default('Pesanan Masuk')
                            ->required(),
                        TextInput::make('total_harga')
                            ->label('Total Harga (Rp)')
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->disabled()
                            ->dehydrated(),
                        Select::make('pelayan_id')
                            ->label('Pelayan')
                            ->relationship('pelayan', 'name')
                            ->preload()
                            ->searchable(),
                    ]),
                Section::make('Item Menu Dipesan')
                    ->schema([
                        Repeater::make('detailPesanans')
                            ->label('Daftar Menu')
                            ->relationship('detailPesanans')
                            ->defaultItems(1)
                            ->minItems(1)
                            ->columns(2)
                            ->schema([
                                Select::make('menu_id')
                                    ->label('Menu')
                                    ->options(fn () => Menu::where('is_aktif', true)->pluck('nama_menu', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, $state): void {
                                        $harga = (float) (Menu::find($state)?->harga ?? 0);
                                        $set('harga_satuan', $harga);
                                        self::hitungSubtotalItem($get, $set);
                                    }),
                                TextInput::make('jumlah')
                                    ->label('Jumlah')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::hitungSubtotalItem($get, $set)),
                                Select::make('condiments')
                                    ->label('Condiment / Tambahan')
                                    ->relationship('condiments', 'nama_condiment')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::hitungSubtotalItem($get, $set)),
                                TextInput::make('harga_satuan')
                                    ->label('Harga Satuan (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('subtotal')
                                    ->label('Subtotal (Rp)')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(),
                                TextInput::make('catatan')
                                    ->label('Catatan Khusus')
                                    ->placeholder('Contoh: Tidak pedas, sedikit es'),
                            ]),
                    ]),
                Section::make('Catatan')
                    ->schema([
                        Textarea::make('catatan')
                            ->label('Catatan Pesanan')
                            ->placeholder('Catatan khusus dari pengunjung...')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected static function hitungSubtotalItem(Get $get, Set $set): void
    {
        $hargaSatuan = (float) ($get('harga_satuan') ?? 0);
        $jumlah = max(1, (int) ($get('jumlah') ?? 1));
        $condimentIds = $get('condiments') ?? [];
        $hargaCondiment = empty($condimentIds)
            ? 0.0
            : (float) Condiment::whereIn('id', $condimentIds)->sum('harga_condiment');

        $set('subtotal', ($hargaSatuan + $hargaCondiment) * $jumlah);
    }
}
