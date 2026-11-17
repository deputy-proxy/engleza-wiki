<?php

namespace App\Filament\Resources\LearningPaths\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LearningPathsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable(),
            TextColumn::make('level.name')->label('CEFR')->sortable(),
            TextColumn::make('status')->badge()->sortable(),
            TextColumn::make('units_count')->counts('units')->label('Units'),
            TextColumn::make('published_at')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
        ])->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }
}
