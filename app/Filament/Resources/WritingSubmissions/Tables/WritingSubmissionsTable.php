<?php

namespace App\Filament\Resources\WritingSubmissions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WritingSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('#')->sortable(),
            TextColumn::make('writingResource.resource.title')->label('Assignment')->searchable()->limit(50)->wrap(),
            TextColumn::make('user.email')->label('User')->searchable(),
            TextColumn::make('word_count')->label('Words')->sortable(),
            TextColumn::make('status')->badge()->colors(['gray' => 'draft', 'success' => 'submitted']),
            TextColumn::make('submitted_at')->dateTime('M d, Y H:i')->placeholder('—'),
            TextColumn::make('created_at')->dateTime('M d, Y')->sortable()->toggleable(),
        ])
            ->filters([SelectFilter::make('status')->options(['draft' => 'Draft', 'submitted' => 'Submitted'])])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
