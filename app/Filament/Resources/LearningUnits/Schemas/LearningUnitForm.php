<?php

namespace App\Filament\Resources\LearningUnits\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LearningUnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Learning unit')->schema([
                Select::make('learning_path_id')->label('Learning path')->relationship('path', 'title')->required()->searchable()->preload(),
                TextInput::make('title')->required(),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
                Toggle::make('is_published')->label('Published')->default(false),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }
}
