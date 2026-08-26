<?php
namespace App\Filament\Resources\Topics\Tables;
use Filament\Actions\{EditAction, DeleteAction, BulkActionGroup, DeleteBulkAction};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class TopicsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('parent.name')->label('Parent')->badge()->color('info')->placeholder('—'),
            TextColumn::make('resources_count')->counts('resources')->label('Resources')->sortable(),
            TextColumn::make('questions_count')->counts('questions')->label('Questions')->sortable(),
            TextColumn::make('sort_order'),
        ])->defaultSort('sort_order')
        ->recordActions([EditAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
