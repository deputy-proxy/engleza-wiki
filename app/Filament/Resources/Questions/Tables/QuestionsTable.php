<?php

namespace App\Filament\Resources\Questions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QuestionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('question')->limit(80)->searchable()->wrap(),
            TextColumn::make('type')->badge()->colors(['primary' => 'single_choice', 'info' => 'multiple_choice', 'warning' => 'true_false', 'success' => 'text']),
            TextColumn::make('difficulty')->badge()->colors(['success' => 'easy', 'warning' => 'medium', 'danger' => 'hard']),
            TextColumn::make('section.title')->label('Test section')->placeholder('—')->toggleable(),
            TextColumn::make('listeningResource.resource.title')->label('Listening')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('readingResource.resource.title')->label('Reading')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('options_count')->counts('options')->label('Options')->sortable(),
            TextColumn::make('points')->sortable(),
        ])
            ->filters([
                SelectFilter::make('type')->options(['single_choice' => 'Single', 'multiple_choice' => 'Multiple', 'true_false' => 'True/False', 'text' => 'Text']),
                SelectFilter::make('difficulty')->options(['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard']),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
