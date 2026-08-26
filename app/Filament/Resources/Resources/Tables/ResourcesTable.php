<?php
namespace App\Filament\Resources\Resources\Tables;

use Filament\Actions\{BulkActionGroup, DeleteBulkAction, EditAction, DeleteAction, Action};
use Filament\Tables\Columns\{IconColumn, TextColumn};
use Filament\Tables\Filters\{SelectFilter, TernaryFilter};
use Filament\Tables\Table;

class ResourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')->searchable()->sortable()->limit(60)->wrap(),
            TextColumn::make('type')->badge()->colors([
                'primary'=>'article','info'=>'grammar','success'=>'vocabulary',
                'warning'=>['worksheet','lesson_plan','activity','guide','exercise'],
                'gray'=>'card','purple'=>'listening','danger'=>'reading','sky'=>'writing',
            ])->sortable(),
            TextColumn::make('level.name')->badge()->color('primary')->placeholder('—')->sortable(),
            TextColumn::make('skills.name')->badge()->separator(', ')->limitList(3),
            TextColumn::make('status')->badge()->colors(['gray'=>'draft','success'=>'published','danger'=>'archived']),
            IconColumn::make('featured')->boolean()->sortable(),
            TextColumn::make('published_at')->dateTime('M d, Y')->sortable()->toggleable(),
            TextColumn::make('author.name')->label('Author')->toggleable(isToggledHiddenByDefault: true),
        ])
        ->filters([
            SelectFilter::make('type')->options([
                'article'=>'Article','grammar'=>'Grammar','vocabulary'=>'Vocabulary','worksheet'=>'Worksheet',
                'lesson_plan'=>'Lesson plan','activity'=>'Activity','guide'=>'Guide','exercise'=>'Exercise',
                'card'=>'Card','listening'=>'Listening','reading'=>'Reading','writing'=>'Writing',
            ]),
            SelectFilter::make('status')->options(['draft'=>'Draft','published'=>'Published','archived'=>'Archived']),
            SelectFilter::make('level')->relationship('level','name'),
            TernaryFilter::make('featured'),
        ])
        ->defaultSort('created_at','desc')
        ->recordActions([EditAction::make(), DeleteAction::make()])
        ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
