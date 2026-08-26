<?php
namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\{Select, TextInput, Textarea, Toggle};
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)
                ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string)$state))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('parent_id')->label('Parent category')->relationship('parent','name')
                ->searchable()->preload(),
            TextInput::make('type')->placeholder('optional grouping'),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
            Toggle::make('is_active')->default(true),
        ])->columns(2);
    }
}
