<?php
namespace App\Filament\Resources\TestAttempts\Schemas;

use Filament\Forms\Components\{DateTimePicker, Placeholder, Select, TextInput, Textarea};
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TestAttemptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Attempt')->columns(2)->schema([
                Select::make('test_id')->label('Test')->relationship('test','title')->searchable()->preload()->required(),
                Select::make('user_id')->label('User')->relationship('user','email')->searchable()->preload()->placeholder('Anonymous'),
                TextInput::make('session_id')->placeholder('anonymous session'),
                Select::make('status')->native(false)->required()->options(['started'=>'Started','completed'=>'Completed','abandoned'=>'Abandoned']),
                DateTimePicker::make('started_at'),
                DateTimePicker::make('completed_at'),
            ]),
            Section::make('Result')->columns(3)->schema([
                TextInput::make('score')->numeric()->readOnly(),
                TextInput::make('percentage')->numeric()->suffix('%')->readOnly(),
                TextInput::make('level_result')->readOnly(),
                TextInput::make('time_spent_seconds')->label('Time (s)')->numeric()->readOnly(),
            ]),
            Section::make('Meta')->collapsed()->columns(2)->schema([
                TextInput::make('ip_address')->readOnly(),
                Textarea::make('user_agent')->rows(2)->columnSpanFull()->readOnly(),
            ]),
        ]);
    }
}
