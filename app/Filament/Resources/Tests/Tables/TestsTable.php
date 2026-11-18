<?php

namespace App\Filament\Resources\Tests\Tables;

use App\Filament\Resources\EditorialReviewActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TestsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable(),
            TextColumn::make('level.name')->badge()->color('primary')->sortable(),
            TextColumn::make('skill.name')->badge()->color('success')->placeholder('—'),
            TextColumn::make('status')->badge()->colors(['gray' => 'draft', 'success' => 'published', 'danger' => 'archived']),
            TextColumn::make('editorial_status')->label('Editorial review')->badge()->colors(['success' => 'approved', 'warning' => ['pending', 'revise'], 'danger' => ['unpublish', 'remove']])->sortable(),
            TextColumn::make('editorialReviewer.name')->label('Reviewer')->placeholder('—')->toggleable(),
            TextColumn::make('editorial_reviewed_at')->label('Reviewed')->dateTime()->placeholder('—')->toggleable(),
            TextColumn::make('access_type')->badge()->colors(['success' => 'free', 'warning' => 'registered', 'danger' => 'premium']),
            TextColumn::make('attempts_count')->counts('attempts')->label('Attempts')->sortable(),
            TextColumn::make('duration_minutes')->label('Time')->suffix(' min')->sortable()->toggleable(),
            IconColumn::make('is_public')->boolean()->toggleable(),
        ])
            ->filters([
                SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
                SelectFilter::make('level')->relationship('level', 'name'),
                SelectFilter::make('skill')->relationship('skill', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), EditorialReviewActions::approve(), EditorialReviewActions::decision('revise', 'Request revision', 'warning'), EditorialReviewActions::decision('unpublish', 'Unpublish', 'danger'), EditorialReviewActions::decision('remove', 'Archive', 'danger'), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
