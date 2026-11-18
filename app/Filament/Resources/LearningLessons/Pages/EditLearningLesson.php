<?php

namespace App\Filament\Resources\LearningLessons\Pages;

use App\Filament\Resources\LearningLessons\LearningLessonResource;
use App\Models\LearningLesson;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLearningLesson extends EditRecord
{
    protected static string $resource = LearningLessonResource::class;

    /** @var array<string, array<int, int|string>> */
    protected array $editorialTaxonomyBefore = [];

    protected function beforeSave(): void
    {
        if (! $this->record instanceof LearningLesson) {
            return;
        }

        foreach (['skills', 'topics'] as $relation) {
            $this->editorialTaxonomyBefore[$relation] = $this->record->{$relation}()->pluck('id')->sort()->values()->all();
        }
    }

    protected function afterSave(): void
    {
        if (! $this->record instanceof LearningLesson) {
            return;
        }

        foreach ($this->editorialTaxonomyBefore as $relation => $before) {
            $after = $this->record->{$relation}()->pluck('id')->sort()->values()->all();
            if ($before !== $after) {
                $this->record->invalidateEditorialReview();
                break;
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
