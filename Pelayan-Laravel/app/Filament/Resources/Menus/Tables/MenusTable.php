<?php

namespace App\Filament\Resources\Menus\Tables;

use App\Models\Menu;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenusTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('gambar')
                    ->label('Foto')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=M&background=f97316&color=fff'),
                TextColumn::make('nama_menu')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kategoriMenu.nama_kategori')
                    ->label('Kategori')
                    ->sortable()
                    ->badge(),
                TextColumn::make('harga')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state): string => 'Rp '.number_format((float) $state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('stok')
                    ->label('Stok')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    }),
                ToggleColumn::make('is_aktif')
                    ->label('Tersedia'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('kategori_menu_id')
                    ->label('Kategori')
                    ->relationship('kategoriMenu', 'nama_kategori'),
                TernaryFilter::make('is_aktif')
                    ->label('Status Aktif'),
            ])
            ->recordActions([
                Action::make('restock')
                    ->label('Restock')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->modalHeading(fn (Menu $record): string => "Tambah Stok — {$record->nama_menu}")
                    ->modalDescription(fn (Menu $record): string => "Stok saat ini: {$record->stok} porsi")
                    ->schema([
                        TextInput::make('jumlah_tambah')
                            ->label('Jumlah Tambah Stok')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(10),
                    ])
                    ->action(function (Menu $record, array $data): void {
                        $jumlah = (int) $data['jumlah_tambah'];
                        $record->increment('stok', $jumlah);

                        Notification::make()
                            ->title("Stok {$record->nama_menu} ditambah +{$jumlah}")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
