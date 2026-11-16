<?php

namespace App\Filament\Resources\Skills\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SkillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn ($s, $set) => $set('slug', Str::slug((string) $s))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            Textarea::make('description')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }
}
