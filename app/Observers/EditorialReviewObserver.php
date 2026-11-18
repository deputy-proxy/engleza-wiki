<?php

namespace App\Observers;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Media;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\ResourceCard;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\WritingResource;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EditorialReviewObserver
{
    public function saved(Model $model): void
    {
        $this->invalidateParent($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidateParent($model);
    }

    private function invalidateParent(Model $model): void
    {
        if ($model instanceof Level || $model instanceof Skill || $model instanceof Topic || $model instanceof Category || $model instanceof Tag || $model instanceof Audience) {
            $this->invalidateTaxonomyDependents($model);

            return;
        }

        $parent = match (true) {
            $model instanceof QuestionOption => $this->questionParent($model->question),
            $model instanceof Question => $this->questionParent($model),
            $model instanceof TestSection => $model->test,
            $model instanceof Media => $model->resource,
            $model instanceof ListeningResource,
            $model instanceof ReadingResource,
            $model instanceof WritingResource,
            $model instanceof CardResource => $model->resource,
            $model instanceof ResourceCard => $model->cardResource->resource,
            $model instanceof LessonActivity => $model->lesson,
            $model instanceof ContentTranslation => $this->translationParent($model),
            default => null,
        };

        if ($parent instanceof Resource || $parent instanceof Test || $parent instanceof LearningLesson) {
            $parent->invalidateEditorialReview();
        }
    }

    private function questionParent(Question $question): ?Model
    {
        if ($question->test_section_id !== null) {
            return $question->section->test;
        }

        if ($question->listening_resource_id !== null) {
            return $question->listeningResource->resource;
        }

        if ($question->reading_resource_id !== null) {
            return $question->readingResource->resource;
        }

        return null;
    }

    private function translationParent(ContentTranslation $translation): ?Model
    {
        $translatable = $translation->translatable;

        if ($translatable === null) {
            return null;
        }

        if ($translatable instanceof Level || $translatable instanceof Skill || $translatable instanceof Topic || $translatable instanceof Category || $translatable instanceof Tag || $translatable instanceof Audience) {
            $this->invalidateTaxonomyDependents($translatable);

            return null;
        }

        if ($translatable instanceof LearningUnit) {
            $this->invalidateLessonCollection($translatable->lessons);

            return null;
        }

        if ($translatable instanceof LearningPath) {
            $lessons = $translatable->units()->with('lessons')->get()->flatMap(fn (LearningUnit $unit) => $unit->lessons);
            $this->invalidateLessonCollection($lessons);

            return null;
        }

        return match (true) {
            $translatable instanceof Resource,
            $translatable instanceof Test,
            $translatable instanceof LearningLesson => $translatable,
            $translatable instanceof TestSection => $translatable->test,
            $translatable instanceof Question => $this->questionParent($translatable),
            $translatable instanceof QuestionOption => $this->questionParent($translatable->question),
            $translatable instanceof ListeningResource,
            $translatable instanceof ReadingResource,
            $translatable instanceof WritingResource,
            $translatable instanceof CardResource => $translatable->resource,
            $translatable instanceof ResourceCard => $translatable->cardResource->resource,
            $translatable instanceof LessonActivity => $translatable->lesson,
            default => null,
        };
    }

    /** @param Collection<int, LearningLesson> $lessons */
    private function invalidateLessonCollection(Collection $lessons): void
    {
        $lessons->each(fn (LearningLesson $lesson) => $lesson->invalidateEditorialReview());
    }

    private function invalidateTaxonomyDependents(Model $taxonomy): void
    {
        $id = $taxonomy->getKey();
        $pending = ['editorial_status' => 'pending', 'editorial_reviewed_by' => null, 'editorial_reviewed_at' => null];

        if ($taxonomy instanceof Level) {
            Resource::query()->where('level_id', $id)->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
            Test::query()->where('level_id', $id)->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
            LearningLesson::query()->where('level_id', $id)->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        } elseif ($taxonomy instanceof Skill) {
            Resource::query()->whereHas('skills', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
            Test::query()->where('skill_id', $id)->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
            LearningLesson::query()->whereHas('skills', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        } elseif ($taxonomy instanceof Topic) {
            Resource::query()->whereHas('topics', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
            LearningLesson::query()->whereHas('topics', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        } elseif ($taxonomy instanceof Category) {
            Resource::query()->whereHas('categories', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        } elseif ($taxonomy instanceof Tag) {
            Resource::query()->whereHas('tags', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        } elseif ($taxonomy instanceof Audience) {
            Resource::query()->whereHas('audiences', fn ($query) => $query->whereKey($id))->whereIn('editorial_status', ['approved', 'noindex'])->update($pending);
        }
    }
}
