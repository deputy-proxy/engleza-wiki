<?php

namespace App\Filament\Resources\ClassGroups\Schemas;

use App\Models\ClassGroup;
use App\Models\Test;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ClassGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Class')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(120),
                TextInput::make('join_code')->maxLength(12)->helperText('Auto-generated if left empty.')
                    ->default(fn () => ClassGroup::generateCode())->unique(ignoreRecord: true),
                Select::make('teacher_id')->label('Teacher')->relationship('teacher', 'email', modifyQueryUsing: fn (Builder $query) => $query->where('role', 'teacher'))->searchable()->required()->disabled(fn (?ClassGroup $record): bool => $record !== null)->dehydrated(fn (?ClassGroup $record): bool => $record === null),
                Select::make('test_id')->label('Assigned test')->options(fn () => Test::query()->published()->where('editorial_status', 'approved')->where('is_public', true)->pluck('title', 'id'))->searchable()->preload(),
                Select::make('writingResources')->label('Assigned writing tasks')->multiple()
                    ->relationship('writingResources', 'id', modifyQueryUsing: fn (Builder $query) => $query->whereHas('resource', fn (Builder $resourceQuery) => $resourceQuery->where('status', 'published')->where('editorial_status', 'approved')->where('type', 'writing')))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->resource->title ?? "Writing task #{$record->id}")
                    ->searchable()->preload()->columnSpanFull(),
                Toggle::make('is_active')->default(true),
                Textarea::make('description')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }
}
