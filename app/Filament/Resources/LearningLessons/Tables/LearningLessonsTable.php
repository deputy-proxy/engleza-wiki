<?php

namespace App\Filament\Resources\LearningLessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LearningLessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('unit.path.title')->label('Path')->searchable(),
            TextColumn::make('unit.title')->label('Unit')->searchable(),
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('level.name')->label('CEFR'),
            TextColumn::make('status')->badge(),
            TextColumn::make('activities_count')->counts('activities')->label('Activities'),
        ])->filters([
            SelectFilter::make('status')->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived']),
        ])->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }
}
