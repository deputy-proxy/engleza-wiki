<?php

namespace App\Filament\Resources\Audiences;

use App\Filament\Resources\Audiences\Pages\CreateAudience;
use App\Filament\Resources\Audiences\Pages\EditAudience;
use App\Filament\Resources\Audiences\Pages\ListAudiences;
use App\Filament\Resources\Audiences\Schemas\AudienceForm;
use App\Filament\Resources\Audiences\Tables\AudiencesTable;
use App\Models\Audience;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AudienceResource extends Resource
{
    protected static ?string $model = Audience::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static string|\UnitEnum|null $navigationGroup = 'Content';
    protected static string|null $navigationLabel = 'Audiences';


    public static function form(Schema $schema): Schema
    {
        return AudienceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AudiencesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAudiences::route('/'),
            'create' => CreateAudience::route('/create'),
            'edit' => EditAudience::route('/{record}/edit'),
        ];
    }
}
