<?php

namespace App\Filament\Resources\ContentTranslations\Tables;

use App\Filament\Resources\EditorialReviewActions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContentTranslationsTable
{
    public static function configure(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('translatable_type')->label('Content type')->formatStateUsing(fn ($state): string => class_basename((string) $state))->sortable(),
            TextColumn::make('translatable_id')->label('Content ID')->sortable(),
            TextColumn::make('locale')->badge()->sortable(),
            TextColumn::make('field')->searchable()->sortable(),
            TextColumn::make('value')->limit(70)->wrap(),
            TextColumn::make('status')->badge(),
            TextColumn::make('editorial_status')->label('Editorial review')->badge()->sortable(),
            TextColumn::make('editorialReviewer.name')->label('Reviewer')->placeholder('—'),
            TextColumn::make('editorial_reviewed_at')->label('Reviewed')->dateTime()->placeholder('—'),
        ])->filters([
            SelectFilter::make('locale')->options(['ro' => 'Romanian']),
            SelectFilter::make('status')->options(['draft' => 'Draft', 'reviewed' => 'Reviewed']),
            SelectFilter::make('editorial_status')->options([
                'pending' => 'Pending', 'approved' => 'Approved', 'revise' => 'Revision required',
                'unpublish' => 'Unpublished', 'remove' => 'Remove',
            ]),
        ])->recordActions([
            EditAction::make(),
            EditorialReviewActions::approve(),
            EditorialReviewActions::decision('revise', 'Request revision', 'warning'),
            EditorialReviewActions::decision('remove', 'Remove translation', 'danger'),
            DeleteAction::make(),
        ])->toolbarActions([
            BulkActionGroup::make([DeleteBulkAction::make()]),
        ])->defaultSort('created_at', 'desc');
    }
}
