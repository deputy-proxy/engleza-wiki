<?php
namespace App\Filament\Resources\AssessmentResults\Tables;
use Filament\Actions\{ViewAction, DeleteAction, BulkActionGroup, DeleteBulkAction};
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssessmentResultsTable {
    public static function configure(Table $table): Table {
        return $table->columns([
            TextColumn::make('id')->label('#'),
            TextColumn::make('attempt.test.title')->label('Test')->searchable(),
            TextColumn::make('attempt.user.email')->label('User')->placeholder('Anonymous'),
            TextColumn::make('level')->badge()->color('primary'),
            TextColumn::make('overall_percentage')->suffix('%')->sortable()
                ->color(fn($state)=>$state===null?'gray':($state>=70?'success':($state>=50?'warning':'danger'))),
            TextColumn::make('grammar_score')->suffix('%')->toggleable(),
            TextColumn::make('vocabulary_score')->suffix('%')->toggleable(),
            TextColumn::make('created_at')->dateTime('M d, Y H:i')->sortable(),
        ])->defaultSort('created_at','desc')
        ->recordActions([ViewAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
