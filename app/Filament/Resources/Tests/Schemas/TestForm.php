<?php
namespace App\Filament\Resources\Tests\Schemas;

use Filament\Forms\Components\{DateTimePicker, Select, TextInput, Textarea, Toggle};
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
                    ->afterStateUpdated(fn ($state, $set) => $set('slug', Str::slug((string)$state))),
                TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Select::make('level_id')->label('Level')->relationship('level','name')->searchable()->preload(),
                Select::make('skill_id')->label('Skill')->relationship('skill','name')->searchable()->preload(),
                Select::make('access_type')->native(false)->required()->options(['free'=>'Free','registered'=>'Registered','premium'=>'Premium'])->default('free'),
                Select::make('status')->native(false)->required()->options(['draft'=>'Draft','published'=>'Published','archived'=>'Archived'])->default('draft'),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                Textarea::make('instructions')->rows(4)->columnSpanFull(),
            ]),
            Section::make('Configuration')->columns(3)->schema([
                TextInput::make('duration_minutes')->numeric()->suffix('min'),
                TextInput::make('passing_score')->numeric()->suffix('%'),
                DateTimePicker::make('published_at'),
                Toggle::make('is_public')->default(true),
                Toggle::make('shuffle_questions')->default(false),
                Toggle::make('show_correct_answers')->default(true),
            ]),
        ]);
    }
}
