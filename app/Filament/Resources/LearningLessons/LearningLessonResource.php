<?php

namespace App\Filament\Resources\LearningLessons;

use App\Filament\Resources\LearningLessons\Pages\CreateLearningLesson;
use App\Filament\Resources\LearningLessons\Pages\EditLearningLesson;
use App\Filament\Resources\LearningLessons\Pages\ListLearningLessons;
use App\Filament\Resources\LearningLessons\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\LearningLessons\Schemas\LearningLessonForm;
use App\Filament\Resources\LearningLessons\Tables\LearningLessonsTable;
use App\Models\LearningLesson;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LearningLessonResource extends Resource
{
    protected static ?string $model = LearningLesson::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Learning';

    protected static ?string $navigationLabel = 'Lessons';

    public static function form(Schema $schema): Schema
    {
        return LearningLessonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LearningLessonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLearningLessons::route('/'),
            'create' => CreateLearningLesson::route('/create'),
            'edit' => EditLearningLesson::route('/{record}/edit'),
        ];
    }
}
