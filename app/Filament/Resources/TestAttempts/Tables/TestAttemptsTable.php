<?php

namespace App\Filament\Resources\TestAttempts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TestAttemptsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('#')->sortable(),
            TextColumn::make('test.title')->label('Test')->searchable()->sortable(),
            TextColumn::make('user.email')->label('User')->placeholder('Anonymous')->searchable(),
            TextColumn::make('status')->badge()->colors(['gray' => 'started', 'success' => 'completed', 'danger' => 'abandoned']),
            TextColumn::make('percentage')->suffix('%')->sortable()->color(fn ($state) => $state === null ? 'gray' : ($state >= 70 ? 'success' : ($state >= 50 ? 'warning' : 'danger'))),
            TextColumn::make('level_result')->badge()->color('primary')->placeholder('—'),
            TextColumn::make('completed_at')->dateTime('M d, Y H:i')->sortable(),
        ])
            ->filters([
                SelectFilter::make('status')->options(['started' => 'Started', 'completed' => 'Completed', 'abandoned' => 'Abandoned']),
                SelectFilter::make('test')->relationship('test', 'title'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
