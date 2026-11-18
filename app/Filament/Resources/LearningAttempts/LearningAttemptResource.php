<?php

namespace App\Filament\Resources\LearningAttempts;

use App\Filament\Resources\Concerns\AdminOnlyLearnerData;
use App\Filament\Resources\LearningAttempts\Pages\CreateLearningAttempt;
use App\Filament\Resources\LearningAttempts\Pages\EditLearningAttempt;
use App\Filament\Resources\LearningAttempts\Pages\ListLearningAttempts;
use App\Filament\Resources\LearningAttempts\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\LearningAttempts\Schemas\LearningAttemptForm;
use App\Filament\Resources\LearningAttempts\Tables\LearningAttemptsTable;
use App\Models\LearningAttempt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LearningAttemptResource extends Resource
{
    use AdminOnlyLearnerData;

    protected static ?string $model = LearningAttempt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|\UnitEnum|null $navigationGroup = 'Assessments';

    protected static ?string $navigationLabel = 'Learning Attempts';

    public static function form(Schema $schema): Schema
    {
        return LearningAttemptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningAttemptsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningAttempts::route('/'),
            'create' => CreateLearningAttempt::route('/create'),
            'edit' => EditLearningAttempt::route('/{record}/edit'),
        ];
    }
}
