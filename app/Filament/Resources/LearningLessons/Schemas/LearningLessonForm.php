<?php

namespace App\Filament\Resources\LearningLessons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningLessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Lesson')->schema([
                Select::make('learning_unit_id')->label('Unit')->relationship('unit', 'title')->required()->searchable()->preload(),
                TextInput::make('title')->required(),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('level_id')->label('CEFR level')->relationship('level', 'name')->searchable()->preload(),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Select::make('status')->required()->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')
                    ->helperText('Publishing requires a published unit, a publication date and at least one activity linked to published content.'),
                DateTimePicker::make('published_at'),
                Textarea::make('description')->rows(3)->columnSpanFull(),
                Repeater::make('objectives')->simple(TextInput::make('objective')->required())->columnSpanFull(),
                Select::make('skills')->relationship('skills', 'name')->multiple()->preload()->columnSpanFull(),
                Select::make('topics')->relationship('topics', 'name')->multiple()->preload()->columnSpanFull(),
            ])->columns(2),
            Section::make('Editorial review')->collapsed()->columns(2)->schema([
                TextInput::make('editorial_status')->disabled()->dehydrated(false),
                TextInput::make('editorialReviewer.name')->label('Reviewer')->disabled()->dehydrated(false),
                TextInput::make('editorial_reviewed_at')->label('Reviewed at')->disabled()->dehydrated(false),
                Textarea::make('editorial_notes')->label('Review notes')->rows(3)->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
        ]);
    }
}
