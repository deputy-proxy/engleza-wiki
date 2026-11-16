<?php

namespace App\Filament\Resources\Levels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LevelsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('slug')->badge()->color('primary'),
            TextColumn::make('resources_count')->counts('resources')->sortable(),
            TextColumn::make('tests_count')->counts('tests')->sortable(),
        ])->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
