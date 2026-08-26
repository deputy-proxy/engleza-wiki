<?php
namespace App\Filament\Resources\Levels\Schemas;
use Filament\Forms\Components\{TextInput, Textarea};
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
class LevelForm {
    public static function configure(Schema $schema): Schema {
        return $schema->components([
            TextInput::make('name')->required()->live(onBlur: true)->afterStateUpdated(fn($s,$set)=>$set('slug',Str::slug((string)$s))),
            TextInput::make('slug')->required()->unique(ignoreRecord: true),
            TextInput::make('sort_order')->numeric()->default(0),
            Textarea::make('description')->rows(3)->columnSpanFull(),
        ])->columns(2);
    }
}
