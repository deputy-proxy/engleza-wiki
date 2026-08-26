<?php

namespace App\Filament\Resources\Resources\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ResourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('excerpt')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('content')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('type')
                    ->required()
                    ->default('article'),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                TextInput::make('level_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('author_id')
                    ->numeric()
                    ->default(null),
                Toggle::make('featured')
                    ->required(),
                DateTimePicker::make('published_at'),
                TextInput::make('seo_title')
                    ->default(null),
                Textarea::make('seo_description')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('canonical_url')
                    ->url()
                    ->default(null),
                Toggle::make('robots_index')
                    ->required(),
                TextInput::make('og_title')
                    ->default(null),
                Textarea::make('og_description')
                    ->default(null)
                    ->columnSpanFull(),
                FileUpload::make('og_image')
                    ->image(),
            ]);
    }
}
