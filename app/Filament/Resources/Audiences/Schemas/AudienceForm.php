<?php

namespace App\Filament\Resources\Audiences\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class AudienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn ($s, $set) => $set('slug', Str::slug((string) $s))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
        ])->columns(2);
    }
}
