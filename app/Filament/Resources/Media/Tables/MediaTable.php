<?php

namespace App\Filament\Resources\Media\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MediaTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('filename')->searchable()->sortable(),
            TextColumn::make('resource.title')->label('Attached to')->placeholder('—'),
            TextColumn::make('mime_type')->badge(),
            TextColumn::make('size')->label('Size')->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 0).' KB' : '—'),
            TextColumn::make('download_count')->label('Downloads')->sortable(),
            TextColumn::make('created_at')->dateTime('M d, Y')->sortable(),
        ])->defaultSort('created_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
