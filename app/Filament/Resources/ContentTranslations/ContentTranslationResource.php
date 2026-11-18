<?php

namespace App\Filament\Resources\ContentTranslations;

use App\Filament\Resources\ContentTranslations\Pages\CreateContentTranslation;
use App\Filament\Resources\ContentTranslations\Pages\EditContentTranslation;
use App\Filament\Resources\ContentTranslations\Pages\ListContentTranslations;
use App\Filament\Resources\ContentTranslations\Schemas\ContentTranslationForm;
use App\Filament\Resources\ContentTranslations\Tables\ContentTranslationsTable;
use App\Models\ContentTranslation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentTranslationResource extends Resource
{
    protected static ?string $model = ContentTranslation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    protected static string|\UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Translations';

    protected static ?string $modelLabel = 'translation';

    protected static ?string $pluralModelLabel = 'translations';

    public static function form(Schema $schema): Schema
    {
        return ContentTranslationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentTranslationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContentTranslations::route('/'),
            'create' => CreateContentTranslation::route('/create'),
            'edit' => EditContentTranslation::route('/{record}/edit'),
        ];
    }
}
