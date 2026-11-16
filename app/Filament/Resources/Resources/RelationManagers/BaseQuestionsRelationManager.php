<?php

namespace App\Filament\Resources\Resources\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Manages questions attached to a Listening or Reading resource.
 * Not registered directly on ResourceResource. Instead, this class is a helper
 * used by the ListeningQuestions/ReadingQuestions relation managers below.
 */
abstract class BaseQuestionsRelationManager extends RelationManager
{
    protected static ?string $recordTitleAttribute = 'question';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('question')->required()->rows(2)->columnSpanFull(),
            Textarea::make('explanation')->rows(2)->columnSpanFull(),
            Select::make('type')->options(['single_choice' => 'Single choice', 'multiple_choice' => 'Multiple choice', 'true_false' => 'True/False', 'text' => 'Text'])->default('single_choice')->required(),
            TextInput::make('points')->numeric()->default(1),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#'),
                TextColumn::make('question')->limit(60)->searchable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('options_count')->counts('options')->label('Options'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
