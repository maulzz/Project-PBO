<?php

namespace App\Filament\Pemilik\Resources;

use App\Filament\Pemilik\Resources\Pages\ListMonitoringStoks;
use App\Models\Menu;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class MonitoringStokResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $slug = 'monitoring-stok';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Pemantauan Stok';

    protected static ?string $navigationLabel = 'Stok Menu';

    protected static ?string $modelLabel = 'Stok Menu';

    protected static ?string $pluralModelLabel = 'Monitoring Stok Menu';

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

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('kategoriMenu'))
            ->defaultSort('stok', 'asc')
            ->columns([
                TextColumn::make('nama_menu')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('kategoriMenu.nama_kategori')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('harga')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('stok')
                    ->label('Sisa Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->formatStateUsing(fn (int $state): string => $state <= 0 ? 'Habis (0)' : "{$state} porsi"),
                IconColumn::make('is_aktif')
                    ->label('Aktif')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('kategori_menu_id')
                    ->label('Kategori')
                    ->relationship('kategoriMenu', 'nama_kategori'),
                SelectFilter::make('kondisi_stok')
                    ->label('Kondisi Stok')
                    ->options([
                        'habis' => 'Stok Habis (0)',
                        'menipis' => 'Hampir Habis (1–5)',
                        'aman' => 'Stok Aman (> 5)',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'habis' => $query->where('stok', '<=', 0),
                        'menipis' => $query->whereBetween('stok', [1, 5]),
                        'aman' => $query->where('stok', '>', 5),
                        default => $query,
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMonitoringStoks::route('/'),
        ];
    }
}
