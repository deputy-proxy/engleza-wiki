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
            ->with(['translations', 'level.translations', 'skills.translations', 'topics.translations', 'units.lessons.translations', 'units.lessons.level.translations', 'units.lessons.skills.translations', 'units.lessons.topics.translations', 'units.lessons.activities.translations', 'units.lessons.activities.resource.translations', 'units.lessons.activities.resource.level.translations', 'units.lessons.activities.resource.speakingResource.translations', 'units.lessons.activities.test.translations', 'units.lessons.activities.test.level.translations', 'units.lessons.activities.test.skill.translations'])
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
        $path->load(['translations', 'level.translations', 'skills.translations', 'topics.translations', 'units.lessons.translations', 'units.lessons.level.translations', 'units.lessons.skills.translations', 'units.lessons.topics.translations', 'units.lessons.activities.translations', 'units.lessons.activities.resource.translations', 'units.lessons.activities.resource.level.translations', 'units.lessons.activities.resource.speakingResource.translations', 'units.lessons.activities.test.translations', 'units.lessons.activities.test.level.translations', 'units.lessons.activities.test.skill.translations']);
        abort_unless($path->units->contains(fn ($unit): bool => $unit->is_published
            && $unit->lessons->contains(fn (LearningLesson $lesson): bool => $this->isLessonAvailable($lesson))), 404);

        return $path;
    }

    public function lesson(LearningLesson|string $lesson, int $userId): LearningLesson
    {
        $lesson = $lesson instanceof LearningLesson
            ? $lesson
            : LearningLesson::query()->where('slug', $lesson)->firstOrFail();
        $lesson->load(['translations', 'unit.path.translations', 'level.translations', 'skills.translations', 'topics.translations', 'activities.translations', 'activities.resource.translations', 'activities.resource.level.translations', 'activities.resource.skills.translations', 'activities.resource.topics.translations', 'activities.resource.speakingResource.translations', 'activities.test.translations', 'activities.test.level.translations', 'activities.test.skill.translations']);
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

    public function completeActivity(LessonActivity $activity, int $userId, ?int $attemptId = null, bool $selfAttested = false): LessonActivityProgress
    {
        $lesson = $activity->lesson()->with(['translations', 'unit.path.translations'])->firstOrFail();
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
        } elseif ($activity->type === 'resource' && $activity->resource?->type === 'speaking') {
            abort_unless($selfAttested && $activity->resource->speakingResource !== null
                && $activity->resource->speakingResource->publicationIssues() === [], 403);
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
            abort_unless($requiredIds->diff($completedIds)->isEmpty(), 422, __('Complete all required activities before completing this lesson.'));

            $progress = LessonProgress::query()->firstOrCreate(
                ['learning_lesson_id' => $lesson->id, 'user_id' => $userId],
                ['status' => 'started', 'started_at' => now()],
            );
            $progress->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            app(EngagementService::class)->awardLessonCompletion($userId, (int) $lesson->id);

            $path = $lesson->unit->path;
            $availableLessons = $path->units()->where('is_published', true)->with(['lessons.translations', 'lessons.activities.translations', 'lessons.activities.resource.translations', 'lessons.activities.test.translations'])
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
        $lesson->load('unit.path', 'activities.resource.speakingResource', 'activities.resource', 'activities.test');
        $unit = $lesson->unit;
        $path = $unit?->path;
        if (! $unit || ! $unit->is_published || ! $path || $path->status !== 'published' || ! in_array($lesson->editorial_status, ['approved', 'grandfathered'], true)
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
                if ($resource === null || $resource->status !== 'published' || ! $resource->isEditorialVisible()
                    || ($resource->published_at !== null && $resource->published_at->gt(now()))
                    || ! $this->hasValidQuestionsForActivity($activity)) {
                    return false;
                }
                if ($resource->type === 'speaking' && ($resource->speakingResource === null || $resource->speakingResource->publicationIssues() !== [])) {
                    return false;
                }
            } elseif ($activity->type === 'test') {
                $test = $activity->test;
                if ($test === null || $test->status !== 'published' || ! $test->isEditorialVisible() || ! $test->is_public
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

    public function isLessonIndexable(LearningLesson $lesson): bool
    {
        if ($lesson->editorial_status !== 'approved' || ! $this->isLessonAvailable($lesson)) {
            return false;
        }

        foreach ($lesson->activities as $activity) {
            if ($activity->type === 'resource' && $activity->resource?->editorial_status !== 'approved') {
                return false;
            }

            if ($activity->type === 'test' && $activity->test?->editorial_status !== 'approved') {
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
                ? $resource->listeningResource()->with(['translations', 'questions.translations', 'questions.options', 'questions.topics.translations'])->first()
                : $resource->readingResource()->with(['translations', 'questions.translations', 'questions.options', 'questions.topics.translations'])->first();
            $questions = $interactive === null ? collect() : $interactive->questions;
        } else {
            $test = $activity->test;
            if ($test === null) {
                return false;
            }
            $questions = $test->sections()->with(['questions.translations', 'questions.options', 'questions.topics.translations'])->get()->flatMap(fn ($section) => $section->questions);
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
                abort_unless($complete, 403, __('Complete earlier lessons before continuing.'));
            }
        }

        abort(404);
    }
}
