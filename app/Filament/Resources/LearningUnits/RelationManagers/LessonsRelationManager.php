<?php

namespace App\Filament\Resources\LearningUnits\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Lessons';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required(),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Select::make('level_id')->label('CEFR level')->relationship('level', 'name')->searchable()->preload(),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
            Select::make('status')->required()->options(['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'])->default('draft')
                ->helperText('Publishing requires a published unit, a publication date and at least one activity linked to published content.'),
            DateTimePicker::make('published_at'),
            Textarea::make('description')->rows(3)->columnSpanFull(),
            Select::make('skills')->relationship('skills', 'name')->multiple()->preload()->columnSpanFull(),
            Select::make('topics')->relationship('topics', 'name')->multiple()->preload()->columnSpanFull(),
            Repeater::make('objectives')->simple(TextInput::make('objective')->required())->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('title')->searchable(),
            TextColumn::make('status')->badge(),
            TextColumn::make('activities_count')->counts('activities')->label('Activities'),
        ])->defaultSort('sort_order')->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
