<?php

namespace App\Filament\Resources\LearningPaths\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningPathForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Learning path')->schema([
                TextInput::make('title')->required()->maxLength(255),
                TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Select::make('level_id')->label('CEFR level')->relationship('level', 'name')->searchable()->preload(),
                Textarea::make('description')->rows(4)->columnSpanFull(),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Select::make('status')->required()->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')
                    ->helperText('Publishing requires at least one published unit with a published lesson and valid published activities.'),
                DateTimePicker::make('published_at'),
                Select::make('skills')->relationship('skills', 'name')->multiple()->preload()->columnSpanFull(),
                Select::make('topics')->relationship('topics', 'name')->multiple()->preload()->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
