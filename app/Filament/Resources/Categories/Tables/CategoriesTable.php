<?php
namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\{BulkActionGroup, DeleteBulkAction, EditAction, DeleteAction};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('parent.name')->label('Parent')->badge()->color('info')->placeholder('—'),
            TextColumn::make('resources_count')->counts('resources')->label('Resources')->sortable(),
            IconColumn::make('is_active')->boolean(),
            TextColumn::make('sort_order')->sortable(),
        ])
        ->defaultSort('sort_order')
        ->recordActions([EditAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
