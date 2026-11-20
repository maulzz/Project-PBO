<?php

namespace App\Filament\Resources\Condiments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CondimentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Condiment')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nama_condiment')
                            ->label('Nama Condiment')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Extra Shot, Gula Aren, Keju'),
                        TextInput::make('harga_condiment')
                            ->label('Harga Tambahan (Rp)')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->default(0)
                            ->placeholder('0'),
                        Toggle::make('status_ketersediaan')
                            ->label('Tersedia')
                            ->default(true)
                            ->helperText('Nonaktifkan jika condiment sedang habis'),
                    ]),
            ]);
    }
}
