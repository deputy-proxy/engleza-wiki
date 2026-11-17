<?php

namespace App\Filament\Resources\LessonActivities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonActivityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Activity')->schema([
                Select::make('learning_lesson_id')->label('Lesson')->relationship('lesson', 'title')->required()->searchable()->preload(),
                Select::make('type')->required()->options(['resource' => 'Resource', 'test' => 'Test'])->live()->default('resource')
                    ->afterStateUpdated(fn ($state, $set) => $state === 'resource' ? $set('test_id', null) : $set('resource_id', null)),
                Select::make('resource_id')->label('Existing resource')->relationship('resource', 'title')->searchable()->preload()
                    ->visible(fn ($get): bool => $get('type') === 'resource')->required(fn ($get): bool => $get('type') === 'resource'),
                Select::make('test_id')->label('Existing test')->relationship('test', 'title')->searchable()->preload()
                    ->visible(fn ($get): bool => $get('type') === 'test')->required(fn ($get): bool => $get('type') === 'test'),
                TextInput::make('title')->helperText('Optional title override.'),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_required')->default(true),
                Textarea::make('instructions')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
