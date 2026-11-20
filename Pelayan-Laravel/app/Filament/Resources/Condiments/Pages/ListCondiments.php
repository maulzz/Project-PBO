<?php

namespace App\Filament\Resources\Condiments\Pages;

use App\Filament\Resources\Condiments\CondimentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCondiments extends ListRecords
{
    protected static string $resource = CondimentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
