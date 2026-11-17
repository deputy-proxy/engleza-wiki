<?php

namespace App\Filament\Resources\LearningUnits;

use App\Filament\Resources\LearningUnits\Pages\CreateLearningUnit;
use App\Filament\Resources\LearningUnits\Pages\EditLearningUnit;
use App\Filament\Resources\LearningUnits\Pages\ListLearningUnits;
use App\Filament\Resources\LearningUnits\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\LearningUnits\Schemas\LearningUnitForm;
use App\Filament\Resources\LearningUnits\Tables\LearningUnitsTable;
use App\Models\LearningUnit;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LearningUnitResource extends Resource
{
    protected static ?string $model = LearningUnit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?string $navigationLabel = 'Units';

    public static function form(Schema $schema): Schema
    {
        return LearningUnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningUnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [LessonsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningUnits::route('/'),
            'create' => CreateLearningUnit::route('/create'),
            'edit' => EditLearningUnit::route('/{record}/edit'),
        ];
    }
}
