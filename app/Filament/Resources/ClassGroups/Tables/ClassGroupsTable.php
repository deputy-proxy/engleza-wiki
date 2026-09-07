<?php
namespace App\Filament\Resources\ClassGroups\Tables;
use Filament\Actions\{EditAction, DeleteAction, ViewAction, BulkActionGroup, DeleteBulkAction, Action};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Table;

class ClassGroupsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('join_code')->badge()->color('primary')->copyable()->copyMessage('Join code copied'),
            TextColumn::make('teacher.email')->label('Teacher')->searchable(),
            TextColumn::make('test.title')->label('Assigned test')->placeholder('—')->limit(40),
            TextColumn::make('attempts_count')->counts('attempts')->label('Attempts')->sortable(),
            IconColumn::make('is_active')->boolean(),
        ])->defaultSort('created_at','desc')
        ->recordActions([
            Action::make('results')->label('Results')->icon('heroicon-o-chart-bar')->color('success')
                ->url(fn ($record) => route('classes.results', $record->join_code)),
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
