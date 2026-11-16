<?php

namespace App\Filament\Resources\Questions\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $recordTitleAttribute = 'value';

    protected static ?string $title = 'Answer options';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->maxLength(4)->placeholder('A')->helperText('A, B, C…'),
            Textarea::make('value')->rows(2)->required()->columnSpanFull(),
            Toggle::make('is_correct')->label('Correct answer'),
            TextInput::make('sort_order')->numeric()->default(0),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('sort_order')->label('#')->sortable(),
            TextColumn::make('label')->badge()->color('primary'),
            TextColumn::make('value')->limit(80)->wrap(),
            IconColumn::make('is_correct')->boolean(),
        ])->defaultSort('sort_order')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
