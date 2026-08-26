<?php

namespace App\Filament\Resources\Tests\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('instructions')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('level_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('skill_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('duration_minutes')
                    ->numeric()
                    ->default(null),
                TextInput::make('passing_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('access_type')
                    ->required()
                    ->default('free'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                Toggle::make('is_public')
                    ->required(),
                Toggle::make('shuffle_questions')
                    ->required(),
                Toggle::make('show_correct_answers')
                    ->required(),
                DateTimePicker::make('published_at'),
            ]);
    }
}
