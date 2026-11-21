<?php

namespace Database\Seeders;

use App\Models\ClassGroup;
use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Learning\ClassGroupService;
use App\Services\Test\TestAttemptService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Browser fixtures may only be seeded in the testing environment.');
        }

        $reviewer = User::query()->where('role', 'admin')->orderBy('id')->firstOrFail();
        $review = [
            'editorial_status' => 'approved',
            'editorial_reviewed_by' => $reviewer->id,
            'editorial_reviewed_at' => now(),
        ];

        foreach ([Resource::class, Assessment::class, LearningLesson::class, ContentTranslation::class] as $model) {
            $model::query()->where('editorial_status', 'pending')->update($review);
        }

        $teacher = User::query()->where('email', 'teacher@engleza.wiki')->firstOrFail();
        $learner = User::query()->where('email', 'learner@engleza.wiki')->firstOrFail();
        $writingResource = Resource::query()->where('type', 'writing')->where('status', 'published')->firstOrFail();

        $assessment = Assessment::query()->where('slug', 'vocabulary-test-everyday-english')->firstOrFail();
        $completedAssessment = TestAttempt::query()
            ->where('test_id', $assessment->id)
            ->where('user_id', $learner->id)
            ->where('status', 'completed')
            ->latest('id')
            ->first();
        if (! $completedAssessment) {
            $attempts = app(TestAttemptService::class);
            $attempt = $attempts->start($assessment, [
                'user_id' => $learner->id,
                'session_id' => null,
                'started_at' => now(),
                'status' => 'started',
            ]);
            $attempts->complete($attempt, $learner->id, null);
        }
        $group = ClassGroup::query()->firstOrCreate(
            ['join_code' => 'E2E26A'],
            ['name' => 'Browser E2E Writing Class', 'teacher_id' => $teacher->id, 'is_active' => true],
        );
        $group->writingResources()->sync([$writingResource->writingResource()->firstOrFail()->id]);
        app(ClassGroupService::class)->join($learner, $group, $teacher);

        $path = LearningPath::query()->firstOrCreate(
            ['slug' => 'e2e-browser-learning-path'],
            ['title' => 'Browser E2E Learning Path', 'description' => 'A deterministic path for browser progression checks.', 'status' => 'draft', 'published_at' => now()],
        );
        $unit = LearningUnit::query()->firstOrCreate(
            ['learning_path_id' => $path->id, 'sort_order' => 1],
            ['title' => 'Browser E2E Unit', 'description' => 'A unit used by the browser test suite.', 'is_published' => true],
        );
        for ($number = 1; $number <= 2; $number++) {
            $lesson = LearningLesson::query()->firstOrCreate(
                ['slug' => 'e2e-browser-lesson-'.$number],
                ['learning_unit_id' => $unit->id, 'title' => 'Browser E2E Lesson '.$number, 'description' => 'A browser progression lesson.', 'objectives' => ['Complete the assigned activity.'], 'status' => 'draft', 'published_at' => now(), 'sort_order' => $number],
            );
            LessonActivity::query()->firstOrCreate(
                ['learning_lesson_id' => $lesson->id, 'sort_order' => 1],
                ['type' => 'resource', 'resource_id' => Resource::query()->where('slug', 'understanding-the-present-simple')->value('id'), 'title' => 'Read the Present Simple lesson', 'is_required' => true],
            );
            if ($lesson->editorial_status === 'pending') {
                $lesson->approveEditorialReview($reviewer);
            }
            if ($lesson->status !== 'published') {
                $lesson->update(['status' => 'published']);
            }
        }
        if ($path->status !== 'published') {
            $path->update(['status' => 'published']);
        }

        $lessonIds = $path->units()->with('lessons')->get()->flatMap(fn (LearningUnit $unit) => $unit->lessons->pluck('id'));
        $activityIds = LessonActivity::query()->whereIn('learning_lesson_id', $lessonIds)->pluck('id');
        DB::table('lesson_activity_progress')->where('user_id', $learner->id)->whereIn('lesson_activity_id', $activityIds)->delete();
        DB::table('lesson_progress')->where('user_id', $learner->id)->whereIn('learning_lesson_id', $lessonIds)->delete();
        DB::table('experience_point_awards')->where('user_id', $learner->id)->where('source_type', 'lesson')->whereIn('source_id', $lessonIds)->delete();
        DB::table('learning_path_enrollments')->where('user_id', $learner->id)->where('learning_path_id', $path->id)->delete();
    }
}
