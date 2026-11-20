<?php

namespace App\Filament\Resources\Mejas\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class MejaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Meja')
                    ->columns(2)
                    ->schema([
                        TextInput::make('nomor_meja')
                            ->label('Nomor Meja')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: M01'),
                        TextInput::make('qr_code')
                            ->label('QR Code Identifier')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'QR-'.strtoupper(Str::random(8)))
                            ->helperText('Kode unik untuk generate QR Code')
                            ->suffixAction(
                                Action::make('generateQr')
                                    ->label('Generate Ulang')
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(fn (Set $set) => $set('qr_code', 'QR-'.strtoupper(Str::random(8)))),
                            ),
                        TextInput::make('kapasitas')
                            ->label('Kapasitas (Orang)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('4'),
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'tersedia' => 'Tersedia',
                                'terisi' => 'Terisi',
                            ])
                            ->default('tersedia')
                            ->required(),
                    ]),
            ]);
    }
}
