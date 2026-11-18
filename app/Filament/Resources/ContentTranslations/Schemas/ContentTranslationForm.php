<?php

namespace App\Filament\Resources\ContentTranslations\Schemas;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Category;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\WritingResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentTranslationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Translation source')->columns(2)->schema([
                Select::make('translatable_type')->label('Content type')->required()->native(false)->options([
                    Resource::class => 'Resource',
                    Test::class => 'Test',
                    LearningLesson::class => 'Learning lesson',
                    LearningPath::class => 'Learning path',
                    LearningUnit::class => 'Learning unit',
                    LessonActivity::class => 'Lesson activity',
                    Question::class => 'Question',
                    TestSection::class => 'Test section',
                    CardResource::class => 'Flashcard set',
                    ListeningResource::class => 'Listening activity',
                    ReadingResource::class => 'Reading activity',
                    WritingResource::class => 'Writing activity',
                    Level::class => 'CEFR level',
                    Skill::class => 'Skill',
                    Topic::class => 'Topic',
                    Category::class => 'Category',
                    Audience::class => 'Audience',
                    Tag::class => 'Tag',
                ]),
                TextInput::make('translatable_id')->label('Content record ID')->numeric()->required(),
                Select::make('locale')->required()->native(false)->options(['ro' => 'Romanian'])->default('ro'),
                TextInput::make('field')->required()->maxLength(100),
            ]),
            Section::make('Translated content')->schema([
                Textarea::make('value')->required()->rows(12)->columnSpanFull(),
            ]),
            Section::make('Editorial review')->columns(2)->schema([
                TextInput::make('status')->label('Translation status')->disabled()->dehydrated(false),
                TextInput::make('editorial_status')->label('Editorial status')->disabled()->dehydrated(false),
                TextInput::make('editorialReviewer.name')->label('Reviewed by')->disabled()->dehydrated(false),
                TextInput::make('editorial_reviewed_at')->label('Reviewed at')->disabled()->dehydrated(false),
                Textarea::make('editorial_notes')->label('Review notes')->rows(3)->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
        ]);
    }
}
