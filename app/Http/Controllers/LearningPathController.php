<?php

namespace App\Http\Controllers;

use App\Models\LearningAttempt;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\LessonActivity;
use App\Models\LessonActivityProgress;
use App\Models\LessonProgress;
use App\Models\TestAttempt;
use App\Models\WritingSubmission;
use App\Services\Learning\LearningPathService;
use App\Services\Learning\VocabularyReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function index(LearningPathService $learning): View
    {
        $paths = $learning->availablePaths();

        return view('pages.learning.index', compact('paths'));
    }

    public function show(LearningPath $path, LearningPathService $learning): View
    {
        $path = $learning->path($path);
        $enrollment = auth()->check()
            ? LearningPathEnrollment::query()->where('learning_path_id', $path->id)->where('user_id', auth()->id())->first()
            : null;
        $availableLessonsByUnit = $path->units->mapWithKeys(fn ($unit): array => [
            $unit->id => $unit->lessons->filter(fn (LearningLesson $lesson): bool => $learning->isLessonAvailable($lesson)),
        ]);
        $progress = auth()->check()
            ? LessonProgress::query()->where('user_id', auth()->id())
                ->whereIn('learning_lesson_id', $path->units->flatMap(fn ($unit) => $unit->lessons->pluck('id')))
                ->get()->keyBy('learning_lesson_id')
            : collect();

        return view('pages.learning.show', compact('path', 'enrollment', 'progress', 'availableLessonsByUnit'));
    }

    public function enroll(LearningPath $path, LearningPathService $learning): RedirectResponse
    {
        $learning->enroll($path, (int) auth()->id());

        return redirect()->route('learning.paths.show', $path);
    }

    public function lesson(LearningLesson $lesson, LearningPathService $learning, VocabularyReviewService $reviews): View
    {
        $lesson = $learning->lesson($lesson, (int) auth()->id());
        $reviews->encounterLesson(auth()->user(), $lesson);
        $lessonProgress = $learning->startLesson($lesson, (int) auth()->id());
        $activityProgress = LessonActivityProgress::query()->where('user_id', auth()->id())
            ->whereIn('lesson_activity_id', $lesson->activities->pluck('id'))->get()->keyBy('lesson_activity_id');

        $attemptIds = [];
        $activityCompletionReady = [];
        foreach ($lesson->activities as $activity) {
            if ($activity->type === 'test') {
                $attemptIds[$activity->id] = TestAttempt::query()->where('test_id', $activity->test_id)
                    ->where('user_id', auth()->id())->where('status', 'completed')->latest('completed_at')->value('id');
                $activityCompletionReady[$activity->id] = $attemptIds[$activity->id] !== null;
            } elseif ($activity->type === 'resource' && in_array($activity->resource?->type, ['listening', 'reading'], true)) {
                $attemptIds[$activity->id] = LearningAttempt::query()->where('resource_id', $activity->resource_id)
                    ->where('user_id', auth()->id())->where('status', 'completed')->latest('completed_at')->value('id');
                $activityCompletionReady[$activity->id] = $attemptIds[$activity->id] !== null;
            } elseif ($activity->type === 'resource' && $activity->resource?->type === 'writing') {
                $writingResourceId = $activity->resource->writingResource?->id;
                $activityCompletionReady[$activity->id] = $writingResourceId !== null && WritingSubmission::query()
                    ->where('writing_resource_id', $writingResourceId)->where('user_id', auth()->id())
                    ->where('status', 'submitted')->exists();
            } else {
                $activityCompletionReady[$activity->id] = $activity->type === 'resource' && $activity->resource?->type === 'speaking'
                    ? ($activity->resource->speakingResource !== null && $activity->resource->speakingResource->publicationIssues() === [])
                    : true;
            }
        }

        return view('pages.learning.lesson', compact('lesson', 'activityProgress', 'attemptIds', 'activityCompletionReady', 'lessonProgress'));
    }

    public function completeActivity(Request $request, LessonActivity $activity, LearningPathService $learning): RedirectResponse
    {
        $data = $request->validate(['attempt_id' => ['nullable', 'integer'], 'self_attested' => ['sometimes', 'accepted']]);
        $learning->completeActivity($activity, (int) auth()->id(), isset($data['attempt_id']) ? (int) $data['attempt_id'] : null, (bool) ($data['self_attested'] ?? false));

        return back()->with('status', __('Activity completed.'));
    }

    public function completeLesson(LearningLesson $lesson, LearningPathService $learning): RedirectResponse
    {
        $learning->completeLesson($lesson, (int) auth()->id());

        return redirect()->route('learning.lessons.show', $lesson)->with('status', __('Lesson completed. You earned 20 XP.'));
    }

    public function myLearning(Request $request, LearningPathService $learning): View
    {
        $userId = (int) $request->user()->id;
        $enrollments = $request->user()->learningPathEnrollments()
            ->with(['path.translations', 'path.level.translations', 'path.units.lessons.translations', 'path.units.lessons.progress', 'path.units.lessons.activities.translations', 'path.units.lessons.activities.resource.translations', 'path.units.lessons.activities.resource.level.translations', 'path.units.lessons.activities.test.translations', 'path.units.lessons.activities.test.level.translations', 'path.units.lessons.activities.test.skill.translations'])
            ->latest('activated_at')->get();

        foreach ($enrollments as $enrollment) {
            $availableLessons = $enrollment->path->units->where('is_published', true)
                ->flatMap(fn ($unit) => $unit->lessons->filter(fn (LearningLesson $lesson): bool => $learning->isLessonAvailable($lesson)))
                ->values();
            $completedCount = $availableLessons->filter(fn (LearningLesson $lesson): bool => $lesson->progress
                ->contains(fn ($progress): bool => (int) $progress->user_id === $userId && $progress->status === 'completed'))->count();
            $nextLesson = $availableLessons->first(fn (LearningLesson $lesson): bool => ! $lesson->progress
                ->contains(fn ($progress): bool => (int) $progress->user_id === $userId && $progress->status === 'completed'));

            $enrollment->setRelation('availableLessons', $availableLessons);
            $enrollment->setRelation('nextLesson', $nextLesson);
            $enrollment->setAttribute('completed_lesson_count', $completedCount);
            $enrollment->setAttribute('available_lesson_count', $availableLessons->count());
        }

        return view('pages.dashboard.learning', compact('enrollments'));
    }
}
