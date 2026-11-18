<?php

namespace Tests\Feature;

use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Question;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\User;
use App\Rules\ValidQuestionMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LearningPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_enrolled_learner_progresses_sequentially_through_lessons(): void
    {
        [$path, $lessons, $activities] = $this->makePathWithLessons(2);
        $user = User::factory()->create(['role' => 'learner']);

        $this->actingAs($user)->post(route('learning.paths.enroll', $path))->assertRedirect(route('learning.paths.show', $path));
        $this->get(route('learning.lessons.show', $lessons[0]))->assertOk()->assertSee($lessons[0]->title);
        $this->get(route('resources.show', $activities[0]->resource))
            ->assertOk()->assertSee('Parte dintr-un parcurs de învățare')->assertSee($lessons[0]->title);
        $this->get(route('learning.lessons.show', $lessons[1]))->assertForbidden();

        $this->post(route('learning.activities.complete', $activities[0]))->assertRedirect();
        $this->post(route('learning.lessons.complete', $lessons[0]))->assertRedirect(route('learning.lessons.show', $lessons[0]));
        $this->assertDatabaseHas('lesson_progress', [
            'learning_lesson_id' => $lessons[0]->id, 'user_id' => $user->id, 'status' => 'completed',
        ]);
        $this->assertDatabaseHas('lesson_activity_progress', [
            'lesson_activity_id' => $activities[0]->id, 'user_id' => $user->id, 'status' => 'completed',
        ]);

        $this->get(route('learning.lessons.show', $lessons[1]))->assertOk()->assertSee($lessons[1]->title);
        $this->get(route('dashboard.learning'))->assertOk()->assertSee($path->title);
    }

    public function test_draft_and_archived_resources_hide_their_published_lessons(): void
    {
        foreach (['draft', 'archived'] as $status) {
            [$path, $lessons] = $this->makePathWithLessons();
            $lesson = $lessons[0];
            $lesson->activities->first()->resource->update(['status' => $status]);

            $this->get(route('learning.paths.show', $path))->assertNotFound();
            $this->actingAs(User::factory()->create(['role' => 'learner']))
                ->get(route('learning.lessons.show', $lesson))->assertNotFound();
        }
    }

    public function test_filament_question_metadata_rule_rejects_invalid_structured_definitions(): void
    {
        $messages = [];
        (new ValidQuestionMetadata('matching'))->validate('metadata', '{"pairs":[{"left":"cat","right":"gato"}]}', function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
        self::assertNotEmpty($messages);

        $messages = [];
        (new ValidQuestionMetadata('ordering'))->validate('metadata', 'null', function (string $message) use (&$messages): void {
            $messages[] = $message;
        });
        self::assertNotEmpty($messages);
    }

    public function test_lesson_publication_requires_valid_interactive_question_definitions(): void
    {
        $resource = Resource::factory()->create(['type' => 'reading', 'status' => 'published']);
        $reading = ReadingResource::create([
            'resource_id' => $resource->id, 'title' => 'Reading exercise', 'instructions' => 'Read and answer.',
            'content' => 'A short text.', 'show_correct_answers' => true,
        ]);
        $question = Question::create([
            'reading_resource_id' => $reading->id, 'question' => 'Match the words.', 'type' => 'matching',
            'metadata' => ['pairs' => [['left' => 'cat', 'right' => 'gato']]], 'points' => 1, 'sort_order' => 1,
        ]);
        $path = LearningPath::create([
            'title' => 'Interactive validation', 'slug' => 'interactive-validation-'.uniqid(),
            'status' => 'draft', 'published_at' => now(),
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id, 'title' => 'Unit', 'sort_order' => 1, 'is_published' => true,
        ]);
        $lesson = LearningLesson::create([
            'learning_unit_id' => $unit->id, 'title' => 'Lesson', 'slug' => 'interactive-lesson-'.uniqid(),
            'status' => 'draft', 'published_at' => now(), 'sort_order' => 1,
        ]);
        LessonActivity::create([
            'learning_lesson_id' => $lesson->id, 'type' => 'resource', 'resource_id' => $resource->id,
            'sort_order' => 1, 'is_required' => true,
        ]);

        try {
            $lesson->update(['status' => 'published']);
            self::fail('A matching question with only one pair must not be publishable.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('status', $exception->errors());
        }

        $question->update(['metadata' => ['pairs' => [
            ['left' => 'cat', 'right' => 'gato'], ['left' => 'dog', 'right' => 'perro'],
        ]]]);
        $lesson->update(['status' => 'published']);
        $path->update(['status' => 'published']);
        $this->assertDatabaseHas('learning_lessons', ['id' => $lesson->id, 'status' => 'published']);
    }

    public function test_published_lesson_activities_cannot_be_changed_without_unpublishing_the_lesson(): void
    {
        [, $lessons] = $this->makePathWithLessons();
        $lesson = $lessons[0];
        $activity = $lesson->activities->first();

        try {
            $activity->update(['sort_order' => 2]);
            self::fail('Published lesson activities must not be changed in place.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('learning_lesson_id', $exception->errors());
        }

        $lesson->update(['status' => 'draft']);
        $activity->update(['sort_order' => 2]);
        $lesson->update(['status' => 'published']);
        $this->assertDatabaseHas('lesson_activities', ['id' => $activity->id, 'sort_order' => 2]);
    }

    public function test_admin_can_manage_the_curriculum_hierarchy_from_parent_records(): void
    {
        [$path, $lessons, $activities, $unit] = $this->makePathWithLessons(1);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/learning-paths/'.$path->slug.'/edit')->assertOk();
        $this->get('/admin/learning-units/'.$unit->id.'/edit')->assertOk();
        $this->get('/admin/learning-lessons/'.$lessons[0]->slug.'/edit')->assertOk();
        $this->get('/admin/lesson-activities/'.$activities[0]->id.'/edit')->assertOk();
    }

    /** @return array{0: LearningPath, 1: array<int, LearningLesson>, 2: array<int, LessonActivity>, 3: LearningUnit} */
    private function makePathWithLessons(int $lessonCount = 1): array
    {
        $path = LearningPath::create([
            'title' => 'Foundation English', 'slug' => 'foundation-english-'.uniqid(),
            'description' => 'A structured path.', 'status' => 'draft', 'published_at' => now(),
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id, 'title' => 'Unit one', 'sort_order' => 1, 'is_published' => true,
        ]);
        $lessons = [];
        $activities = [];
        for ($i = 1; $i <= $lessonCount; $i++) {
            $lesson = LearningLesson::create([
                'learning_unit_id' => $unit->id, 'title' => 'Lesson '.$i, 'slug' => 'lesson-'.$i.'-'.uniqid(),
                'description' => 'Lesson description.', 'objectives' => ['Learn something useful'],
                'status' => 'draft', 'published_at' => now(), 'sort_order' => $i,
            ]);
            $resource = Resource::factory()->create(['type' => 'article', 'status' => 'published']);
            $activity = LessonActivity::create([
                'learning_lesson_id' => $lesson->id, 'type' => 'resource', 'resource_id' => $resource->id,
                'title' => 'Read article '.$i, 'sort_order' => 1, 'is_required' => true,
            ]);
            $lesson->update(['status' => 'published']);
            $lessons[] = $lesson;
            $activities[] = $activity;
        }
        $path->update(['status' => 'published']);

        return [$path, $lessons, $activities, $unit];
    }
}
