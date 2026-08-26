<?php

namespace App\Filament\Resources\AssessmentResults\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class AssessmentResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_attempt_id')
                    ->required()
                    ->numeric(),
                TextInput::make('overall_score')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('overall_percentage')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('level')
                    ->default(null),
                TextInput::make('grammar_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('vocabulary_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('reading_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('writing_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('listening_score')
                    ->numeric()
                    ->default(null),
                TextInput::make('speaking_score')
                    ->numeric()
                    ->default(null),
                Textarea::make('recommendation')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
