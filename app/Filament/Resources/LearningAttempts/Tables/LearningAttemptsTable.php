<?php
namespace App\Filament\Resources\LearningAttempts\Tables;
use Filament\Actions\{ViewAction, EditAction, DeleteAction, BulkActionGroup, DeleteBulkAction};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LearningAttemptsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('id')->label('#')->sortable(),
            TextColumn::make('resource.title')->label('Resource')->searchable()->limit(50)->wrap(),
            TextColumn::make('resource.type')->badge()->colors(['purple'=>'listening','danger'=>'reading']),
            TextColumn::make('user.email')->placeholder('Anonymous'),
            TextColumn::make('status')->badge()->colors(['gray'=>'started','success'=>'completed','danger'=>'abandoned']),
            TextColumn::make('percentage')->suffix('%')->sortable()
                ->color(fn($state)=>$state===null?'gray':($state>=70?'success':($state>=50?'warning':'danger'))),
            TextColumn::make('completed_at')->dateTime('M d, Y H:i')->placeholder('—'),
        ])
        ->filters([
            SelectFilter::make('status')->options(['started'=>'Started','completed'=>'Completed','abandoned'=>'Abandoned']),
        ])
        ->defaultSort('created_at','desc')
        ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
