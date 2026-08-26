<?php

namespace App\Filament\Resources\Resources\RelationManagers;

use App\Models\{CardResource, ResourceCard};
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

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->type === 'card';
    }

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
                TextColumn::make('front')->limit(60)->wrap()->searchable(),
                TextColumn::make('back')->limit(60)->wrap()->searchable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->using(function (array $data): ResourceCard {
                    $cardResource = CardResource::firstOrCreate(['resource_id' => $this->getOwnerRecord()->id]);
                    return ResourceCard::create([
                        'card_resource_id' => $cardResource->id,
                        'front' => $data['front'],
                        'back' => $data['back'],
                        'sort_order' => $data['sort_order'] ?? 0,
                    ]);
                }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
