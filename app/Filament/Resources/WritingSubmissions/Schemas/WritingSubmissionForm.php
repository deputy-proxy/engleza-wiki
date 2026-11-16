<?php

namespace App\Filament\Resources\WritingSubmissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WritingSubmissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Submission')->columns(2)->schema([
                Select::make('writing_resource_id')->label('Writing assignment')->relationship('writingResource', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($r) => $r->resource?->title ?? "#{$r->id}")->searchable()->required(),
                Select::make('user_id')->label('User')->relationship('user', 'email')->searchable()->required(),
                TextInput::make('word_count')->numeric()->readOnly(),
                Select::make('status')->native(false)->options(['draft' => 'Draft', 'submitted' => 'Submitted'])->required(),
            ]),
            Section::make('Content')->schema([
                Textarea::make('content')->rows(16)->columnSpanFull()->required(),
            ]),
        ]);
    }
}
