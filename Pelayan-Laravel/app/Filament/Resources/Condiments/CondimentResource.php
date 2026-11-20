<?php

namespace App\Filament\Resources\Condiments;

use App\Filament\Resources\Condiments\Pages\CreateCondiment;
use App\Filament\Resources\Condiments\Pages\EditCondiment;
use App\Filament\Resources\Condiments\Pages\ListCondiments;
use App\Filament\Resources\Condiments\Schemas\CondimentForm;
use App\Filament\Resources\Condiments\Tables\CondimentsTable;
use App\Models\Condiment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CondimentResource extends Resource
{
    protected static ?string $model = Condiment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Menu';

    protected static ?string $navigationLabel = 'Condiment';

    protected static ?string $modelLabel = 'Condiment';

    protected static ?string $pluralModelLabel = 'Condiment';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return CondimentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CondimentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCondiments::route('/'),
            'create' => CreateCondiment::route('/create'),
            'edit' => EditCondiment::route('/{record}/edit'),
        ];
    }
}
