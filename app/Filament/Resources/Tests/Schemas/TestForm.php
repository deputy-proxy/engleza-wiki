<?php

namespace App\Filament\Resources\Tests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Test')->columns(2)->schema([
                TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string) $state))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('level_id')->label('Level')->relationship('level', 'name')->searchable()->preload(),
                Select::make('skill_id')->label('Skill')->relationship('skill', 'name')->searchable()->preload(),
                Select::make('access_type')->native(false)->required()->options(['free' => 'Free', 'registered' => 'Registered', 'premium' => 'Premium'])->default('free'),
                Select::make('status')->native(false)->required()->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft'),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                Textarea::make('instructions')->rows(4)->columnSpanFull(),
            ]),
            Section::make('Editorial review')->collapsed()->columns(2)->schema([
                TextInput::make('editorial_status')->disabled()->dehydrated(false),
                TextInput::make('editorialReviewer.name')->label('Reviewer')->disabled()->dehydrated(false),
                TextInput::make('editorial_reviewed_at')->label('Reviewed at')->disabled()->dehydrated(false),
                Textarea::make('editorial_notes')->label('Review notes')->rows(3)->disabled()->dehydrated(false)->columnSpanFull(),
            ]),
            Section::make('Configuration')->columns(3)->schema([
                TextInput::make('duration_minutes')->numeric()->suffix('min'),
                TextInput::make('passing_score')->numeric()->step(1)->minValue(0)->maxValue(100)->suffix('%'),
                DateTimePicker::make('published_at'),
                Toggle::make('is_public')->default(true),
                Toggle::make('shuffle_questions')->default(false),
                Toggle::make('show_correct_answers')->default(true),
            ]),
        ]);
    }
}
