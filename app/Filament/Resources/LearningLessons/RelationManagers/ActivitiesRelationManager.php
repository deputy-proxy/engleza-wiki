<?php

namespace App\Filament\Resources\LearningLessons\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $title = 'Ordered activities';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')->required()->options(['resource' => 'Resource', 'test' => 'Test'])->live()->default('resource')
                ->afterStateUpdated(fn ($state, $set) => $state === 'resource' ? $set('test_id', null) : $set('resource_id', null))
                ->helperText('Unpublish the lesson before editing or removing activities, then republish it to validate the complete lesson again.'),
            Select::make('resource_id')->label('Existing resource')->relationship('resource', 'title')->searchable()->preload()
                ->visible(fn ($get): bool => $get('type') === 'resource')->required(fn ($get): bool => $get('type') === 'resource')
                ->helperText('Only published resources can appear in a published lesson.'),
            Select::make('test_id')->label('Existing test')->relationship('test', 'title')->searchable()->preload()
                ->visible(fn ($get): bool => $get('type') === 'test')->required(fn ($get): bool => $get('type') === 'test')
                ->helperText('Only published, accessible tests can appear in a published lesson.'),
            TextInput::make('title')->helperText('Optional title override.'),
            TextInput::make('sort_order')->numeric()->default(0)->required(),
            Toggle::make('is_required')->default(true),
            Textarea::make('instructions')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('title')->placeholder('Use linked content title'),
            TextColumn::make('resource.title')->label('Resource'),
            TextColumn::make('test.title')->label('Test'),
            IconColumn::make('is_required')->boolean(),
        ])->defaultSort('sort_order')->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
