<?php

namespace App\Filament\Resources\Mejas\Pages;

use App\Filament\Resources\Mejas\MejaResource;
use App\Filament\Resources\Mejas\Tables\MejasTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeja extends EditRecord
{
    protected static string $resource = MejaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            MejasTable::qrCodeAction(),
            DeleteAction::make(),
        ];
    }
}
