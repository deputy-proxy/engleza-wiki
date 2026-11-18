<?php

namespace App\Filament\Resources\Media\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('resource_id')->label('Resource')->relationship('resource', 'title')->searchable()->preload(),
            FileUpload::make('path')->disk('public')->directory('media')->required(),
            TextInput::make('filename')->required(),
            TextInput::make('mime_type'),
            TextInput::make('size')->numeric()->suffix('bytes'),
            TextInput::make('source_url')->url()->label('Original source URL'),
            TextInput::make('license')->label('Licence / permission basis')->helperText('Record the exact licence and version, or state that this is an original recording.'),
            Textarea::make('attribution')->rows(2)->label('Attribution'),
            Textarea::make('permission_reference')->rows(2)->label('Permission evidence / reference'),
        ])->columns(2);
    }
}
