<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Menu')
                    ->schema([
                        TextInput::make('nama_menu')
                            ->label('Nama Menu')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Es Kopi Susu'),
                        Select::make('kategori_menu_id')
                            ->label('Kategori')
                            ->relationship('kategoriMenu', 'nama_kategori')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->createOptionForm([
                                TextInput::make('nama_kategori')
                                    ->label('Nama Kategori')
                                    ->required(),
                            ]),
                        Textarea::make('deskripsi')
                            ->label('Deskripsi')
                            ->placeholder('Deskripsi menu...')
                            ->columnSpanFull(),
                    ]),
                Section::make('Harga & Stok')
                    ->columns(2)
                    ->schema([
                        TextInput::make('harga')
                            ->label('Harga (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0'),
                        TextInput::make('stok')
                            ->label('Stok')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ]),
                Section::make('Gambar & Status')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('gambar')
                            ->label('Gambar Menu')
                            ->image()
                            ->disk('public')
                            ->directory('menu-images')
                            ->columnSpanFull(),
                        Toggle::make('is_aktif')
                            ->label('Menu Aktif')
                            ->default(true)
                            ->helperText('Nonaktifkan jika menu tidak tersedia sementara'),
                    ]),
                Section::make('Condiment / Pilihan Tambahan')
                    ->schema([
                        Select::make('condiments')
                            ->label('Condiment yang Tersedia')
                            ->relationship('condiments', 'nama_condiment')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                    ]),
            ]);
    }
}
