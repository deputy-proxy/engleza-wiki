<?php

namespace App\Filament\Resources\LessonActivities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('lesson.unit.path.title')->label('Path')->searchable(),
            TextColumn::make('lesson.title')->label('Lesson')->searchable(),
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('title'),
            TextColumn::make('resource.title')->label('Resource'),
            TextColumn::make('test.title')->label('Test'),
            IconColumn::make('is_required')->boolean(),
        ])->filters([
            SelectFilter::make('type')->options(['resource' => 'Resource', 'test' => 'Test']),
        ])->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }
}
