<?php

namespace App\Filament\Resources\WritingSubmissions;

use App\Filament\Resources\Concerns\AdminOnlyLearnerData;
use App\Filament\Resources\WritingSubmissions\Pages\ListWritingSubmissions;
use App\Filament\Resources\WritingSubmissions\Schemas\WritingSubmissionForm;
use App\Filament\Resources\WritingSubmissions\Tables\WritingSubmissionsTable;
use App\Models\WritingSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WritingSubmissionResource extends Resource
{
    use AdminOnlyLearnerData;

    protected static ?string $model = WritingSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|\UnitEnum|null $navigationGroup = 'Assessments';

    protected static ?string $navigationLabel = 'Writing Submissions';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

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
        return WritingSubmissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WritingSubmissionsTable::configure($table);
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
            'index' => ListWritingSubmissions::route('/'),
        ];
    }
}
