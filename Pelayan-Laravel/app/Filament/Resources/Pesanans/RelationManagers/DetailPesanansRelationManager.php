<?php

namespace App\Filament\Resources\Pesanans\RelationManagers;

use App\Filament\Pemilik\Resources\Pages\ViewRiwayatPesanan;
use App\Filament\Resources\Pesanans\Pages\ViewPesanan;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DetailPesanansRelationManager extends RelationManager
{
    protected static string $relationship = 'detailPesanans';

    protected static ?string $title = 'Item Menu Dipesan';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return in_array($pageClass, [ViewPesanan::class, ViewRiwayatPesanan::class], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('menu.nama_menu')
                    ->label('Menu')
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('condiments.nama_condiment')
                    ->label('Condiment / Tambahan')
                    ->badge()
                    ->color('warning')
                    ->separator(', ')
                    ->placeholder('Tidak ada tambahan'),
                TextColumn::make('harga_satuan')
                    ->label('Harga Satuan')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.')),
                TextColumn::make('jumlah')
                    ->label('Qty')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('catatan')
                    ->label('Catatan Khusus')
                    ->placeholder('-')
                    ->wrap(),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->weight('bold'),
            ])
            ->filters([])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
