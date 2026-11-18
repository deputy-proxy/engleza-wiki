<?php

namespace App\Services\Learning;

use App\Models\LearningAttempt;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\LessonActivity;
use App\Models\LessonActivityProgress;
use App\Models\LessonProgress;
use App\Models\TestAttempt;
use App\Models\WritingSubmission;
use App\Services\Assessment\QuestionResponseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LearningPathService
{
    /** @return Collection<int, LearningPath> */
    public function availablePaths(): Collection
    {
        return LearningPath::query()->published()
            ->with(['level', 'skills', 'topics', 'units.lessons.activities.resource', 'units.lessons.activities.test'])
            ->get()
            ->filter(fn (LearningPath $path): bool => $path->units->contains(fn ($unit): bool => $unit->is_published
                && $unit->lessons->contains(fn (LearningLesson $lesson): bool => $this->isLessonAvailable($lesson))))
            ->values();
    }

    public function path(LearningPath|string $path): LearningPath
    {
        $path = $path instanceof LearningPath
            ? $path
            : LearningPath::query()->where('slug', $path)->firstOrFail();
        abort_unless($path->status === 'published' && $path->published_at !== null && $path->published_at->lte(now()), 404);
        $path->load(['level', 'skills', 'topics', 'units.lessons.activities.resource', 'units.lessons.activities.test']);
        abort_unless($path->units->contains(fn ($unit): bool => $unit->is_published
            && $unit->lessons->contains(fn (LearningLesson $lesson): bool => $this->isLessonAvailable($lesson))), 404);

        return $path;
    }

    public function lesson(LearningLesson|string $lesson, int $userId): LearningLesson
    {
        $lesson = $lesson instanceof LearningLesson
            ? $lesson
            : LearningLesson::query()->where('slug', $lesson)->firstOrFail();
        $lesson->load(['unit.path', 'level', 'skills', 'topics', 'activities.resource.level', 'activities.test']);
        abort_unless($this->isLessonAvailable($lesson), 404);
        $this->assertEnrolledAndUnlocked($lesson, $userId);

        return $lesson;
    }

    public function enroll(LearningPath $path, int $userId): LearningPathEnrollment
    {
        $path = $this->path($path);
        abort_unless($path->units->contains(fn ($unit): bool => $unit->is_published
            && $unit->lessons->contains(fn (LearningLesson $lesson): bool => $this->isLessonAvailable($lesson))), 404);

        return LearningPathEnrollment::query()->firstOrCreate(
            ['learning_path_id' => $path->id, 'user_id' => $userId],
            ['status' => 'active', 'activated_at' => now()],
        );
    }

    public function startLesson(LearningLesson $lesson, int $userId): LessonProgress
    {
        $this->assertEnrolledAndUnlocked($lesson, $userId);
        LearningPathEnrollment::query()->where('learning_path_id', $lesson->unit->learning_path_id)
            ->where('user_id', $userId)->where('status', 'completed')
            ->update(['status' => 'active', 'completed_at' => null]);

        return LessonProgress::query()->firstOrCreate(
            ['learning_lesson_id' => $lesson->id, 'user_id' => $userId],
            ['status' => 'started', 'started_at' => now()],
        );
    }

    public function completeActivity(LessonActivity $activity, int $userId, ?int $attemptId = null): LessonActivityProgress
    {
        $lesson = $activity->lesson()->with('unit.path')->firstOrFail();
        $this->assertEnrolledAndUnlocked($lesson, $userId);
        abort_unless($this->isLessonAvailable($lesson), 404);

        if ($activity->type === 'test') {
            abort_unless($attemptId !== null && TestAttempt::query()->whereKey($attemptId)
                ->where('test_id', $activity->test_id)->where('user_id', $userId)->where('status', 'completed')->exists(), 403);
        } elseif ($activity->type === 'resource' && in_array($activity->resource?->type, ['listening', 'reading'], true)) {
            abort_unless($attemptId !== null && LearningAttempt::query()->whereKey($attemptId)
                ->where('resource_id', $activity->resource_id)->where('user_id', $userId)->where('status', 'completed')->exists(), 403);
        } elseif ($activity->type === 'resource' && $activity->resource?->type === 'writing') {
            $writingResourceId = $activity->resource->writingResource?->id;
            abort_unless($writingResourceId !== null && WritingSubmission::query()
                ->where('writing_resource_id', $writingResourceId)->where('user_id', $userId)->where('status', 'submitted')->exists(), 403);
        }

        return LessonActivityProgress::query()->firstOrCreate(
            ['lesson_activity_id' => $activity->id, 'user_id' => $userId],
            ['status' => 'completed', 'completed_at' => now()],
        );
    }

    public function completeLesson(LearningLesson $lesson, int $userId): LessonProgress
    {
        return DB::transaction(function () use ($lesson, $userId): LessonProgress {
            $this->assertEnrolledAndUnlocked($lesson, $userId);
            $requiredIds = $lesson->activities()->where('is_required', true)->pluck('id');
            $completedIds = LessonActivityProgress::query()->where('user_id', $userId)
                ->whereIn('lesson_activity_id', $requiredIds)->pluck('lesson_activity_id');
            abort_unless($requiredIds->diff($completedIds)->isEmpty(), 422, 'Complete all required activities before completing this lesson.');

            $progress = LessonProgress::query()->firstOrCreate(
                ['learning_lesson_id' => $lesson->id, 'user_id' => $userId],
                ['status' => 'started', 'started_at' => now()],
            );
            $progress->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            app(EngagementService::class)->awardLessonCompletion($userId, (int) $lesson->id);

            $path = $lesson->unit->path;
            $availableLessons = $path->units()->where('is_published', true)->with('lessons.activities.resource', 'lessons.activities.test')
                ->get()->flatMap(fn ($unit) => $unit->lessons->filter(fn (LearningLesson $item): bool => $this->isLessonAvailable($item)));
            $completedCount = LessonProgress::query()->where('user_id', $userId)
                ->whereIn('learning_lesson_id', $availableLessons->pluck('id'))
                ->where('status', 'completed')->count();
            if ($availableLessons->isNotEmpty() && $completedCount >= $availableLessons->count()) {
                LearningPathEnrollment::query()->where('learning_path_id', $path->id)->where('user_id', $userId)
                    ->update(['status' => 'completed', 'completed_at' => now()]);
            }

            return $progress;
        });
    }

    public function isLessonAvailable(LearningLesson $lesson): bool
    {
        $unit = $lesson->unit;
        $path = $unit?->path;
        if (! $unit || ! $unit->is_published || ! $path || $path->status !== 'published'
            || $path->published_at === null || $path->published_at->gt(now())
            || $lesson->status !== 'published' || $lesson->published_at === null || $lesson->published_at->gt(now())) {
            return false;
        }

        $activities = $lesson->activities;
        if ($activities->isEmpty()) {
            return false;
        }

        foreach ($activities as $activity) {
            if ($activity->type === 'resource') {
                $resource = $activity->resource;
                if ($resource === null || $resource->status !== 'published'
                    || ($resource->published_at !== null && $resource->published_at->gt(now()))
                    || ! $this->hasValidQuestionsForActivity($activity)) {
                    return false;
                }
            } elseif ($activity->type === 'test') {
                $test = $activity->test;
                if ($test === null || $test->status !== 'published' || ! $test->is_public
                    || ! in_array($test->access_type, ['free', 'registered'], true)
                    || ($test->published_at !== null && $test->published_at->gt(now()))
                    || ! $this->hasValidQuestionsForActivity($activity)) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }

    private function hasValidQuestionsForActivity(LessonActivity $activity): bool
    {
        if ($activity->type === 'resource') {
            $resource = $activity->resource;
            if ($resource === null || ! in_array($resource->type, ['listening', 'reading'], true)) {
                return true;
            }
            $interactive = $resource->type === 'listening'
                ? $resource->listeningResource()->with('questions.options')->first()
                : $resource->readingResource()->with('questions.options')->first();
            $questions = $interactive === null ? collect() : $interactive->questions;
        } else {
            $test = $activity->test;
            if ($test === null) {
                return false;
            }
            $questions = $test->sections()->with('questions.options')->get()->flatMap(fn ($section) => $section->questions);
        }

        if ($questions->isEmpty()) {
            return false;
        }

        foreach ($questions as $question) {
            if (app(QuestionResponseService::class)->definitionIssues($question) !== []) {
                return false;
            }
        }

        return true;
    }

    private function assertEnrolledAndUnlocked(LearningLesson $lesson, int $userId): void
    {
        $unit = $lesson->unit()->with('path.units.lessons.activities.resource', 'path.units.lessons.activities.test')->firstOrFail();
        $path = $unit->path;
        abort_unless($path && $path->status === 'published', 404);
        abort_unless(LearningPathEnrollment::query()->where('learning_path_id', $path->id)->where('user_id', $userId)->exists(), 403);

        foreach ($path->units as $pathUnit) {
            if (! $pathUnit->is_published) {
                continue;
            }
            foreach ($pathUnit->lessons as $previousLesson) {
                if (! $this->isLessonAvailable($previousLesson)) {
                    continue;
                }
                if ($previousLesson->id === $lesson->id) {
                    return;
                }
                $complete = LessonProgress::query()->where('learning_lesson_id', $previousLesson->id)
                    ->where('user_id', $userId)->where('status', 'completed')->exists();
                abort_unless($complete, 403, 'Complete earlier lessons before continuing.');
            }
        }

        abort(404);
    }
}
