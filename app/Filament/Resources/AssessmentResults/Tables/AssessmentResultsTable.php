<?php

namespace App\Filament\Resources\AssessmentResults\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssessmentResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('test_attempt_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overall_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('overall_percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('level')
                    ->searchable(),
                TextColumn::make('grammar_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('vocabulary_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reading_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('writing_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('listening_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('speaking_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
