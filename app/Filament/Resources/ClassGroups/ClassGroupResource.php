<?php

namespace App\Filament\Resources\ClassGroups;

use App\Filament\Resources\ClassGroups\Pages\CreateClassGroup;
use App\Filament\Resources\ClassGroups\Pages\EditClassGroup;
use App\Filament\Resources\ClassGroups\Pages\ListClassGroups;
use App\Filament\Resources\ClassGroups\Schemas\ClassGroupForm;
use App\Filament\Resources\ClassGroups\Tables\ClassGroupsTable;
use App\Filament\Resources\Concerns\AdminOnlyLearnerData;
use App\Models\ClassGroup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClassGroupResource extends Resource
{
    use AdminOnlyLearnerData;

    protected static ?string $model = ClassGroup::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Assessments';

    protected static ?string $navigationLabel = 'Class Groups';

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ClassGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClassGroupsTable::configure($table);
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
            'index' => ListClassGroups::route('/'),
            'create' => CreateClassGroup::route('/create'),
            'edit' => EditClassGroup::route('/{record}/edit'),
        ];
    }
}
