<?php

namespace App\Filament\Resources\ClassGroups\Schemas;

use App\Models\ClassGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ClassGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Class')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('join_code')->maxLength(12)->helperText('Auto-generated if left empty.')
                    ->default(fn () => ClassGroup::generateCode())->unique(ignoreRecord: true),
                Select::make('teacher_id')->label('Teacher')->relationship('teacher', 'email')->searchable()->required()
                    ->default(fn () => auth()->id()),
                Select::make('test_id')->label('Assigned test')->relationship('test', 'title')->searchable()->preload(),
                Select::make('writingResources')->label('Assigned writing tasks')->multiple()
                    ->relationship('writingResources', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->resource->title ?? "Writing task #{$record->id}")
                    ->searchable()->preload()->columnSpanFull(),
                Toggle::make('is_active')->default(true),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }
}
