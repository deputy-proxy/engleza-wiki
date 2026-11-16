<?php

namespace App\Filament\Resources\Resources\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ResourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basic information')->schema([
                TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required(),
                Select::make('type')->required()->native(false)->default('article')->options([
                    'article' => 'Article', 'grammar' => 'Grammar', 'vocabulary' => 'Vocabulary', 'worksheet' => 'Worksheet',
                    'lesson_plan' => 'Lesson plan', 'activity' => 'Activity', 'guide' => 'Guide', 'exercise' => 'Exercise',
                    'card' => 'Card / Flashcards', 'listening' => 'Listening exercise', 'reading' => 'Reading exercise', 'writing' => 'Writing assignment',
                ])->reactive(),
                Select::make('status')->required()->native(false)->default('draft')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
                Textarea::make('excerpt')->rows(2)->columnSpanFull(),
                Textarea::make('content')->rows(8)->columnSpanFull()
                    ->visible(fn (callable $get) => ! in_array($get('type'), ['card', 'listening', 'reading', 'writing'])),
                Toggle::make('featured'),
                DateTimePicker::make('published_at'),
            ])->columns(2),

            Section::make('Taxonomy')->schema([
                Select::make('level_id')->label('Level')->relationship('level', 'name')->searchable()->preload(),
                Select::make('author_id')->label('Author')->relationship('author', 'name')->searchable()->preload(),
                Select::make('skills')->multiple()->relationship('skills', 'name')->preload(),
                Select::make('categories')->multiple()->relationship('categories', 'name')->preload(),
                Select::make('topics')->multiple()->relationship('topics', 'name')->preload(),
                Select::make('audiences')->multiple()->relationship('audiences', 'name')->preload(),
                Select::make('tags')->multiple()->relationship('tags', 'name')->preload(),
            ])->columns(2),

            // Type-specific: Card
            Section::make('Card content')
                ->description('Cards are managed on the edit page → Cards relation manager.')
                ->schema([
                    Textarea::make('cardResource.instructions')->label('Instructions')->rows(2)->columnSpanFull(),
                ])
                ->visible(fn (callable $get) => $get('type') === 'card'),

            // Type-specific: Listening
            Section::make('Listening content')
                ->schema([
                    Textarea::make('listeningResource.instructions')->label('Instructions')->rows(2)->columnSpanFull(),
                    FileUpload::make('listeningResource.media_path')
                        ->label('Audio or video file')
                        ->disk('public')->directory('listening')
                        ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'video/mp4'])
                        ->maxSize(config('engleza.max_media_kb', 51200))
                        ->columnSpanFull()
                        ->live(),
                    View::make('filament.forms.listening-preview')
                        ->visible(fn (callable $get) => filled($get('listeningResource.media_path')))
                        ->columnSpanFull(),
                ])
                ->visible(fn (callable $get) => $get('type') === 'listening'),

            // Type-specific: Reading
            Section::make('Reading content')
                ->schema([
                    TextInput::make('readingResource.title')->label('Passage title'),
                    Textarea::make('readingResource.instructions')->label('Instructions')->rows(2)->columnSpanFull(),
                    Textarea::make('readingResource.content')->label('Reading passage')->rows(12)->columnSpanFull(),
                ])
                ->visible(fn (callable $get) => $get('type') === 'reading'),

            // Type-specific: Writing
            Section::make('Writing task')
                ->schema([
                    Textarea::make('writingResource.prompt')->label('Prompt')->rows(2)->columnSpanFull(),
                    Textarea::make('writingResource.instructions')->label('Instructions')->rows(2)->columnSpanFull(),
                    TextInput::make('writingResource.minimum_words')->numeric()->label('Minimum words'),
                    TextInput::make('writingResource.maximum_words')->numeric()->label('Maximum words'),
                    TextInput::make('writingResource.recommended_time_minutes')->numeric()->label('Recommended time (min)'),
                    Textarea::make('writingResource.rubric')->rows(3)->columnSpanFull(),
                    Textarea::make('writingResource.example_answer')->label('Example answer (optional)')->rows(6)->columnSpanFull(),
                ])->columns(3)
                ->visible(fn (callable $get) => $get('type') === 'writing'),

            Section::make('SEO')->collapsed()->schema([
                TextInput::make('seo_title'),
                Textarea::make('seo_description')->rows(2)->columnSpanFull(),
                TextInput::make('canonical_url')->url(),
                Toggle::make('robots_index'),
                TextInput::make('og_title'),
                Textarea::make('og_description')->rows(2)->columnSpanFull(),
                FileUpload::make('og_image')->image()->disk('public')->directory('og'),
            ])->columns(2),
        ]);
    }
}
