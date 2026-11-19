<?php

namespace Tests\Feature;

use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\TestSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CurriculumPublicationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_fails_when_any_target_level_has_no_published_path(): void
    {
        foreach (['a1', 'a2', 'b1', 'b2'] as $index => $slug) {
            Level::create(['name' => strtoupper($slug), 'slug' => $slug, 'sort_order' => $index]);
        }

        $this->artisan('learning:curriculum-audit')
            ->expectsOutputToContain('No published learning path exists for this level.')
            ->expectsOutputToContain('4 curriculum publication issue(s) found.')
            ->assertExitCode(1);
    }

    public function test_audit_does_not_count_grandfathered_demo_lessons_as_reviewed_curriculum(): void
    {
        $level = Level::create(['name' => 'A1', 'slug' => 'a1', 'sort_order' => 1]);
        foreach (['a2', 'b1', 'b2'] as $index => $slug) {
            Level::create(['name' => strtoupper($slug), 'slug' => $slug, 'sort_order' => $index + 2]);
        }

        $admin = User::factory()->create(['role' => 'admin']);
        $resource = Resource::factory()->create(['level_id' => $level->id, 'author_id' => $admin->id]);
        $resource->forceFill(['editorial_status' => 'grandfathered'])->save();

        $path = LearningPath::create([
            'level_id' => $level->id,
            'title' => 'A1 Demo Path',
            'slug' => 'a1-demo-path',
            'status' => 'draft',
            'published_at' => now(),
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id,
            'title' => 'Demo Unit',
            'sort_order' => 1,
            'is_published' => true,
        ]);
        $lesson = LearningLesson::create([
            'learning_unit_id' => $unit->id,
            'level_id' => $level->id,
            'title' => 'Demo Lesson',
            'slug' => 'a1-demo-lesson',
            'objectives' => ['Practise a simple language point.'],
            'status' => 'draft',
            'published_at' => now(),
        ]);
        $lesson->forceFill(['editorial_status' => 'grandfathered'])->save();
        LessonActivity::create([
            'learning_lesson_id' => $lesson->id,
            'type' => 'resource',
            'resource_id' => $resource->id,
            'sort_order' => 1,
        ]);
        $lesson->update(['status' => 'published']);
        $this->assertSame([], $lesson->publicationIssues());
        DB::table('learning_paths')->where('id', $path->id)->update(['status' => 'published']);

        $this->artisan('learning:curriculum-audit')
            ->expectsOutputToContain('grandfathered demo content does not qualify.')
            ->assertExitCode(1);
    }

    public function test_audit_passes_when_all_four_paths_meet_the_publication_standard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $levels = [];

        foreach (['a1', 'a2', 'b1', 'b2'] as $index => $slug) {
            $levels[$slug] = Level::create([
                'name' => strtoupper($slug),
                'slug' => $slug,
                'sort_order' => $index + 1,
            ]);
        }

        foreach ($levels as $slug => $level) {
            $path = LearningPath::create([
                'level_id' => $level->id,
                'title' => strtoupper($slug).' Learning Path',
                'slug' => $slug.'-learning-path',
                'status' => 'draft',
                'published_at' => now(),
            ]);

            for ($unitOrder = 1; $unitOrder <= 5; $unitOrder++) {
                $unit = LearningUnit::create([
                    'learning_path_id' => $path->id,
                    'title' => 'Unit '.$unitOrder,
                    'sort_order' => $unitOrder,
                    'is_published' => true,
                ]);

                for ($lessonOrder = 1; $lessonOrder <= 4; $lessonOrder++) {
                    $lesson = LearningLesson::create([
                        'learning_unit_id' => $unit->id,
                        'level_id' => $level->id,
                        'title' => 'Lesson '.$unitOrder.'.'.$lessonOrder,
                        'slug' => $slug.'-lesson-'.$unitOrder.'-'.$lessonOrder,
                        'objectives' => ['Demonstrate the target language in a familiar task.'],
                        'status' => 'draft',
                        'published_at' => now(),
                        'sort_order' => $lessonOrder,
                    ]);

                    $resource = Resource::factory()->create([
                        'level_id' => $level->id,
                        'author_id' => $admin->id,
                    ]);
                    $resource->approveEditorialReview($admin);

                    LessonActivity::create([
                        'learning_lesson_id' => $lesson->id,
                        'type' => 'resource',
                        'resource_id' => $resource->id,
                        'sort_order' => 1,
                    ]);

                    if ($lessonOrder === 4) {
                        $assessment = Assessment::factory()->create(['level_id' => $level->id]);
                        $section = TestSection::create([
                            'test_id' => $assessment->id,
                            'title' => 'Unit checkpoint',
                            'instructions' => 'Complete the checkpoint.',
                            'sort_order' => 1,
                        ]);
                        $question = Question::create([
                            'test_section_id' => $section->id,
                            'type' => 'text',
                            'question' => 'Complete the sentence.',
                            'explanation' => 'The accepted form matches the target.',
                            'points' => 1,
                            'sort_order' => 1,
                        ]);
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'value' => 'example',
                            'is_correct' => true,
                            'sort_order' => 1,
                        ]);
                        $assessment->approveEditorialReview($admin);

                        LessonActivity::create([
                            'learning_lesson_id' => $lesson->id,
                            'type' => 'test',
                            'test_id' => $assessment->id,
                            'sort_order' => 2,
                        ]);
                    } else {
                        $practice = Resource::factory()->create([
                            'level_id' => $level->id,
                            'author_id' => $admin->id,
                        ]);
                        $practice->approveEditorialReview($admin);

                        LessonActivity::create([
                            'learning_lesson_id' => $lesson->id,
                            'type' => 'resource',
                            'resource_id' => $practice->id,
                            'sort_order' => 2,
                        ]);
                    }

                    $lesson->approveEditorialReview($admin);
                    $lesson->update(['status' => 'published']);
                }
            }

            $path->update(['status' => 'published']);
        }

        $this->artisan('learning:curriculum-audit')
            ->expectsOutputToContain('A1, A2, B1 and B2 each have published paths meeting the curriculum structure and editorial-readiness checks.')
            ->assertExitCode(0);
    }
}
