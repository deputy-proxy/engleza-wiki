<?php

namespace App\Filament\Resources\LearningAttempts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Attempt')->columns(2)->schema([
                Select::make('resource_id')->label('Resource')->relationship('resource', 'title')->searchable()->required(),
                Select::make('user_id')->label('User')->relationship('user', 'email')->searchable()->placeholder('Anonymous'),
                Select::make('status')->native(false)->options(['started' => 'Started', 'completed' => 'Completed', 'abandoned' => 'Abandoned'])->required(),
                TextInput::make('session_id'),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
            ]),
            Section::make('Result')->columns(2)->schema([
                TextInput::make('score')->numeric()->readOnly(),
                TextInput::make('percentage')->numeric()->suffix('%')->readOnly(),
            ]),
            Section::make('Meta')->collapsed()->columns(2)->schema([
                TextInput::make('ip_address')->readOnly(),
                Textarea::make('user_agent')->rows(2)->columnSpanFull()->readOnly(),
            ]),
        ]);
    }
}
