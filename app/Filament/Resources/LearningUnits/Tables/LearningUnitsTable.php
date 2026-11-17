<?php

namespace App\Filament\Resources\LearningUnits\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LearningUnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('path.title')->label('Path')->searchable(),
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('lessons_count')->counts('lessons')->label('Lessons'),
            IconColumn::make('is_published')->boolean(),
        ])->filters([
            SelectFilter::make('is_published')->options([1 => 'Published', 0 => 'Draft']),
        ])->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }
}
