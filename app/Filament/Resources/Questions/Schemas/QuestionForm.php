<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_section_id')
                    ->required()
                    ->numeric(),
                TextInput::make('type')
                    ->required()
                    ->default('single_choice'),
                Textarea::make('question')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('explanation')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('points')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('difficulty')
                    ->required()
                    ->default('medium'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Textarea::make('metadata')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
