<?php
namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\{BulkActionGroup, DeleteBulkAction, EditAction};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('email')->searchable()->sortable()->copyable(),
            TextColumn::make('role')->badge()->colors([
                'danger'=>'admin','warning'=>'editor','info'=>'teacher','success'=>'learner',
            ])->sortable(),
            IconColumn::make('email_verified_at')->label('Verified')->boolean(),
            TextColumn::make('created_at')->dateTime('M d, Y')->sortable()->toggleable(),
        ])
        ->filters([
            SelectFilter::make('role')->options(['admin'=>'Admin','editor'=>'Editor','teacher'=>'Teacher','learner'=>'Learner']),
        ])
        ->defaultSort('created_at','desc')
        ->recordActions([EditAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
