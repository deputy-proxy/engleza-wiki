<?php

namespace App\Filament\Resources\Questions\Schemas;

use App\Rules\ValidQuestionMetadata;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Question')->columns(2)->schema([
                Textarea::make('question')->required()->rows(3)->columnSpanFull(),
                Select::make('type')->native(false)->required()->default('single_choice')->options([
                    'single_choice' => 'Single choice', 'multiple_choice' => 'Multiple choice', 'true_false' => 'True/False',
                    'text' => 'Text', 'short_text' => 'Short text', 'matching' => 'Matching', 'ordering' => 'Ordering', 'gap_fill' => 'Gap fill',
                ]),
                Select::make('difficulty')->native(false)->required()->default('medium')->options([
                    'easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard',
                ]),
                TextInput::make('points')->required()->numeric()->default(1),
                TextInput::make('sort_order')->required()->numeric()->default(0),
                Textarea::make('explanation')->rows(3)->columnSpanFull()
                    ->helperText('Shown after the user answers.'),
            ]),
            Section::make('Belongs to')->columns(3)->schema([
                Select::make('test_section_id')->label('Test section')->relationship('section', 'title')->searchable()->preload(),
                Select::make('listening_resource_id')->label('Listening resource')->relationship('listeningResource', 'id')->searchable(),
                Select::make('reading_resource_id')->label('Reading resource')->relationship('readingResource', 'id')->searchable(),
            ])->description('A question belongs to one of: a test section, a listening resource, or a reading resource.'),
            Section::make('Exercise configuration')->schema([
                Textarea::make('metadata')->label('Exercise configuration (JSON)')->rows(6)
                    ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '{}')
                    ->dehydrateStateUsing(fn ($state): array => json_decode((string) $state, true) ?: [])
                    ->rules(['json', fn (Get $get): ValidQuestionMetadata => new ValidQuestionMetadata((string) $get('type'))])
                    ->helperText('Matching: pairs [{"left":"cat","right":"gato"}]. Ordering: items ["first","second"]. Gap fill: gaps {"gap_1":["accepted","alternate"]}.'),
            ]),
            Section::make('Topics')->schema([
                Select::make('topics')->relationship('topics', 'name')->multiple()->preload(),
            ]),
        ]);
    }
}
