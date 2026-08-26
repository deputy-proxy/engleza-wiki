<?php
namespace App\Filament\Resources\Tags\Tables;
use Filament\Actions\{EditAction, DeleteAction, BulkActionGroup, DeleteBulkAction};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class TagsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('slug')->badge()->color('gray'),
            TextColumn::make('resources_count')->counts('resources')->sortable(),
        ])->defaultSort('name')
        ->recordActions([EditAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
