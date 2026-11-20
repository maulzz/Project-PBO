<?php

namespace App\Filament\Resources\Condiments\Pages;

use App\Filament\Resources\Condiments\CondimentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCondiment extends EditRecord
{
    protected static string $resource = CondimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
