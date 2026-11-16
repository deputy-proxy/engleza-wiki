<?php

namespace App\Filament\Resources\LearningAttempts\RelationManagers;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AnswersRelationManager extends RelationManager
{
    protected static string $relationship = 'answers';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $title = 'Answers';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')->readOnly(),
            Textarea::make('answer_text')->rows(3)->columnSpanFull()->readOnly(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('question.question')->label('Question')->limit(60)->wrap(),
            TextColumn::make('option.label')->label('Chosen')->badge()->placeholder('—'),
            TextColumn::make('option.value')->label('Answer')->limit(50)->placeholder('—'),
            IconColumn::make('is_correct')->boolean(),
            TextColumn::make('points_awarded')->label('Pts'),
        ]);
    }
}
