<?php

namespace App\Filament\Resources\Mejas\Tables;

use App\Models\Meja;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MejasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_meja')
                    ->label('No. Meja')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qr_code')
                    ->label('QR Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('QR Code disalin!'),
                TextColumn::make('kapasitas')
                    ->label('Kapasitas')
                    ->suffix(' orang')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'tersedia' => 'success',
                        'terisi' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'terisi' => 'Terisi',
                    ]),
            ])
            ->recordActions([
                self::qrCodeAction(),
                Action::make('kosongkan')
                    ->label('Kosongkan')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->requiresConfirmation()
                    ->visible(fn (Meja $record): bool => $record->status === 'terisi')
                    ->action(function (Meja $record): void {
                        $record->update(['status' => 'tersedia']);
                        Notification::make()->title("Meja {$record->nomor_meja} sekarang tersedia")->success()->send();
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

    public static function qrCodeAction(): Action
    {
        return Action::make('qrCode')
            ->label('QR Code')
            ->icon('heroicon-o-qr-code')
            ->color('primary')
            ->button()
            ->size('sm')
            ->modalHeading(fn (Meja $record): string => "QR Code — Meja {$record->nomor_meja}")
            ->modalContent(fn (Meja $record) => view('filament.modals.meja-qr-code', ['meja' => $record]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
