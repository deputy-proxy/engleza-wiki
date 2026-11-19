<?php

namespace Tests\Feature;

use App\Filament\Resources\Resources\Support\NestedTypeContent;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Resource;
use App\Models\SpeakingResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SpeakingPracticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_speaking_resource_provides_guided_practice_without_recording_or_scoring(): void
    {
        $resource = $this->makeSpeakingResource();

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee('Describe a place you enjoy visiting.')
            ->assertSee('narrative fluency')
            ->assertSee('Începe pregătirea')
            ->assertSee('Începe să vorbești')
            ->assertSee('Autoevaluare')
            ->assertSee('Reflecția ta (opțional)')
            ->assertSee('nu înregistrează și nu încarcă vocea')
            ->assertSee('speaking-timer-status')
            ->assertSee('Cronometrul necesită JavaScript')
            ->assertDontSee('getUserMedia')
            ->assertDontSee('MediaRecorder');
    }

    public function test_speaking_resource_without_a_structured_task_is_not_publicly_listed_or_viewable(): void
    {
        $resource = Resource::factory()->create(['type' => 'speaking', 'status' => 'published']);
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertNotFound();
    }

    public function test_editing_speaking_task_invalidates_parent_editorial_approval(): void
    {
        $resource = $this->makeSpeakingResource();
        $this->assertSame('approved', $resource->fresh()->editorial_status);

        $resource->speakingResource->update(['prompt' => 'Describe a memorable journey.']);

        $this->assertSame('pending', $resource->fresh()->editorial_status);
        $this->get(route('resources.show', $resource))->assertNotFound();
    }

    public function test_admin_resource_form_persists_and_hydrates_nested_speaking_content(): void
    {
        $resource = Resource::factory()->create(['type' => 'speaking']);
        $task = [
            'prompt' => 'Explain how you would solve a small problem.',
            'target_skill' => 'explaining a process',
            'preparation_time_seconds' => 45,
            'response_time_seconds' => 120,
            'instructions' => 'Speak in clear steps.',
            'response_expectation' => 'Explain the problem and at least two steps.',
            'success_criteria' => "I explained the problem.\nI used sequencing language.",
            'reflection_prompt' => 'Which step was hardest to explain?',
        ];

        NestedTypeContent::persist($resource, ['speakingResource' => $task]);
        $hydrated = NestedTypeContent::hydrate($resource->fresh(), []);

        self::assertSame($task['prompt'], $hydrated['speakingResource']['prompt']);
        self::assertSame(45, $hydrated['speakingResource']['preparation_time_seconds']);
        $this->assertDatabaseHas('speaking_resources', [
            'resource_id' => $resource->id,
            'target_skill' => 'explaining a process',
        ]);
    }

    public function test_speaking_task_requires_complete_content_and_bounded_timing(): void
    {
        $resource = Resource::factory()->create(['type' => 'speaking']);

        try {
            SpeakingResource::create([
                'resource_id' => $resource->id,
                'prompt' => 'Say something.',
                'target_skill' => 'fluency',
                'preparation_time_seconds' => 301,
                'response_time_seconds' => 10,
                'instructions' => 'Speak.',
                'response_expectation' => 'A short answer.',
                'success_criteria' => 'Clear ideas.',
                'reflection_prompt' => 'How did it go?',
            ]);
            self::fail('Invalid speaking task timing must be rejected.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey('speakingResource', $exception->errors());
        }
    }

    public function test_learning_lesson_cannot_be_published_with_an_incomplete_speaking_resource(): void
    {
        $resource = Resource::factory()->create(['type' => 'speaking']);
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $path = LearningPath::create([
            'title' => 'Incomplete speaking path',
            'slug' => 'incomplete-speaking-path',
            'status' => 'draft',
            'published_at' => now(),
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id,
            'title' => 'Speaking unit',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $lesson = LearningLesson::create([
            'learning_unit_id' => $unit->id,
            'title' => 'Speaking lesson',
            'slug' => 'incomplete-speaking-lesson',
            'status' => 'draft',
            'published_at' => now(),
            'sort_order' => 1,
        ]);
        LessonActivity::create([
            'learning_lesson_id' => $lesson->id,
            'type' => 'resource',
            'resource_id' => $resource->id,
            'sort_order' => 1,
        ]);

        self::assertContains(
            'Every speaking activity must include a complete, valid speaking practice task.',
            $lesson->publicationIssues(),
        );
    }

    public function test_speaking_resource_can_be_completed_in_a_learning_path_only_with_self_attestation(): void
    {
        $resource = $this->makeSpeakingResource();
        $path = LearningPath::create([
            'title' => 'Speaking foundations',
            'slug' => 'speaking-foundations',
            'description' => 'Practise speaking clearly.',
            'status' => 'draft',
            'published_at' => now(),
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id,
            'title' => 'Speaking unit',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $lesson = LearningLesson::create([
            'learning_unit_id' => $unit->id,
            'title' => 'Describe a place',
            'slug' => 'describe-a-place',
            'description' => 'Practise a short description.',
            'status' => 'draft',
            'published_at' => now(),
            'sort_order' => 1,
        ]);
        $activity = LessonActivity::create([
            'learning_lesson_id' => $lesson->id,
            'type' => 'resource',
            'resource_id' => $resource->id,
            'title' => 'Speak for one minute',
            'sort_order' => 1,
            'is_required' => true,
        ]);
        $lesson->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $lesson->update(['status' => 'published']);
        $path->update(['status' => 'published']);
        $learner = User::factory()->create(['role' => 'learner']);
        LearningPathEnrollment::create([
            'learning_path_id' => $path->id,
            'user_id' => $learner->id,
            'status' => 'active',
            'activated_at' => now(),
        ]);

        $this->actingAs($learner)->get(route('learning.lessons.show', $lesson))
            ->assertOk()->assertSee('Am exersat cu voce tare');

        $this->actingAs($learner)->post(route('learning.activities.complete', $activity))
            ->assertForbidden();
        $this->assertDatabaseMissing('lesson_activity_progress', [
            'lesson_activity_id' => $activity->id,
            'user_id' => $learner->id,
        ]);

        $this->actingAs($learner)->post(route('learning.activities.complete', $activity), ['self_attested' => '1'])
            ->assertRedirect();
        $this->assertDatabaseHas('lesson_activity_progress', [
            'lesson_activity_id' => $activity->id,
            'user_id' => $learner->id,
            'status' => 'completed',
        ]);
    }

    private function makeSpeakingResource(): Resource
    {
        $resource = Resource::factory()->create([
            'title' => 'Describe a place you enjoy visiting',
            'slug' => 'describe-a-place-'.uniqid(),
            'type' => 'speaking',
            'status' => 'published',
            'published_at' => now(),
        ]);
        SpeakingResource::create([
            'resource_id' => $resource->id,
            'prompt' => 'Describe a place you enjoy visiting.',
            'target_skill' => 'narrative fluency',
            'preparation_time_seconds' => 30,
            'response_time_seconds' => 90,
            'instructions' => 'Think of a real place. Speak in complete sentences and organise your ideas clearly.',
            'response_expectation' => 'Speak for about one minute. Explain where it is, what you do there and why you enjoy it.',
            'success_criteria' => "I introduced the place.\nI gave at least two relevant details.\nI connected my ideas clearly.",
            'reflection_prompt' => 'Which part was easiest to explain, and what would you improve next time?',
        ]);
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        return $resource->fresh();
    }
}
