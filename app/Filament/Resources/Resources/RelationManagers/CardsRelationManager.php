<?php

namespace App\Filament\Resources\Resources\RelationManagers;

use Filament\Actions\{CreateAction, DeleteAction, EditAction};
use Filament\Forms\Components\{Textarea, TextInput};
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';
    protected static ?string $recordTitleAttribute = 'front';
    protected static ?string $title = 'Cards';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('front')->required()->rows(3)->columnSpanFull(),
            Textarea::make('back')->required()->rows(3)->columnSpanFull(),
            TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('front')->limit(60)->searchable(),
                TextColumn::make('back')->limit(60)->searchable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public function isReadOnly(): bool { return false; }
}
