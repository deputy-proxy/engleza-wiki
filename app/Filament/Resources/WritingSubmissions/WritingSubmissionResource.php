<?php

namespace App\Filament\Resources\WritingSubmissions;

use App\Filament\Resources\WritingSubmissions\Pages\CreateWritingSubmission;
use App\Filament\Resources\WritingSubmissions\Pages\EditWritingSubmission;
use App\Filament\Resources\WritingSubmissions\Pages\ListWritingSubmissions;
use App\Filament\Resources\WritingSubmissions\Schemas\WritingSubmissionForm;
use App\Filament\Resources\WritingSubmissions\Tables\WritingSubmissionsTable;
use App\Models\WritingSubmission;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WritingSubmissionResource extends Resource
{
    protected static ?string $model = WritingSubmission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;
    protected static string|\UnitEnum|null $navigationGroup = 'Assessments';
    protected static string|null $navigationLabel = 'Writing Submissions';


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
            'create' => CreateWritingSubmission::route('/create'),
            'edit' => EditWritingSubmission::route('/{record}/edit'),
        ];
    }
}
