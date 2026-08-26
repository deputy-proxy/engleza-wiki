<?php

namespace App\Filament\Resources\TestAttempts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class TestAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('test_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('session_id')
                    ->default(null),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
                TextInput::make('status')
                    ->required()
                    ->default('started'),
                TextInput::make('score')
                    ->numeric()
                    ->default(null),
                TextInput::make('percentage')
                    ->numeric()
                    ->default(null),
                TextInput::make('level_result')
                    ->default(null),
                TextInput::make('time_spent_seconds')
                    ->numeric()
                    ->default(null),
                TextInput::make('ip_address')
                    ->default(null),
                Textarea::make('user_agent')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
