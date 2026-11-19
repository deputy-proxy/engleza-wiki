<?php

namespace Tests\Feature;

use App\Filament\Resources\WritingSubmissions\WritingSubmissionResource;
use App\Models\ClassGroup;
use App\Models\ClassGroupMembership;
use App\Models\Resource;
use App\Models\User;
use App\Models\WritingSubmission;
use App\Models\WritingSubmissionReview;
use App\Services\Learning\ClassGroupService;
use App\Services\Privacy\PersonalDataExport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WritingSubmissionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $learner;

    private User $teacher;

    private User $otherTeacher;

    private ClassGroup $group;

    private Resource $writingResource;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->approveSeededContent();

        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->otherTeacher = User::factory()->create(['role' => 'teacher']);
        $this->group = ClassGroup::query()->create([
            'name' => 'Writing class', 'join_code' => 'WRITE01', 'teacher_id' => $this->teacher->id,
        ]);
        $this->writingResource = Resource::query()->where('type', 'writing')->where('status', 'published')->firstOrFail();
        $this->writingResource->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $this->group->writingResources()->attach($this->writingResource->writingResource->id);
        app(ClassGroupService::class)->join($this->learner, $this->group);
    }

    public function test_class_assignment_is_available_to_learners_and_submission_is_linked_to_class(): void
    {
        $this->actingAs($this->learner)->get(route('classes.show', $this->group))->assertOk()->assertSee($this->writingResource->translated('title'));

        $this->actingAs($this->learner)->post(route('classes.writing.submit', [$this->group, $this->writingResource]), [
            'content' => 'A short sample response for the class assignment.',
            'status' => 'submitted',
        ])->assertRedirect();

        $submission = WritingSubmission::query()->firstOrFail();
        $this->assertSame($this->group->id, $submission->class_group_id);
        $this->assertSame($this->learner->id, $submission->user_id);
        $this->assertSame('submitted', $submission->status);
        $this->assertDatabaseHas('writing_submission_versions', [
            'writing_submission_id' => $submission->id, 'version_number' => 1,
        ]);
    }

    public function test_only_the_owning_teacher_or_admin_can_view_class_submissions(): void
    {
        $submission = $this->createClassSubmission();

        $this->actingAs($this->teacher)->get(route('classes.writing.index', $this->group))
            ->assertOk()->assertSee($this->learner->name);
        $this->actingAs($this->otherTeacher)->get(route('classes.writing.index', $this->group))->assertForbidden();
        $this->actingAs($this->otherTeacher)->get(route('classes.writing.show', [$this->group, $submission]))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('classes.writing.index', $this->group))->assertForbidden();
    }

    public function test_teacher_cannot_see_a_learner_draft_before_it_is_submitted(): void
    {
        $this->actingAs($this->learner)->post(route('classes.writing.submit', [$this->group, $this->writingResource]), [
            'content' => 'Private draft text.', 'status' => 'draft',
        ])->assertRedirect();
        $draft = WritingSubmission::query()->firstOrFail();

        $this->actingAs($this->teacher)->get(route('classes.writing.index', $this->group))
            ->assertOk()->assertDontSee('Private draft text.');
        $this->actingAs($this->teacher)->get(route('classes.writing.show', [$this->group, $draft]))->assertNotFound();
        $this->actingAs($this->learner)->get(route('dashboard.writing.show', $draft))->assertOk()->assertSee('Private draft text.');
    }

    public function test_removed_learner_writing_is_hidden_from_teacher_lists_and_direct_review_routes(): void
    {
        $submission = $this->createClassSubmission();
        $membership = ClassGroupMembership::query()->where('class_group_id', $this->group->id)->where('user_id', $this->learner->id)->whereNull('removed_at')->firstOrFail();
        app(ClassGroupService::class)->remove($this->group, $membership, $this->teacher);
        $this->actingAs($this->teacher)->get(route('classes.writing.index', $this->group))->assertOk()->assertDontSee($this->learner->name);
        $this->actingAs($this->teacher)->get(route('classes.writing.show', [$this->group, $submission]))->assertNotFound();
        $this->actingAs($this->teacher)->post(route('classes.writing.begin-review', [$this->group, $submission]))->assertNotFound();
        $this->actingAs($this->teacher)->post(route('classes.writing.request-revision', [$this->group, $submission]))->assertNotFound();
        $this->actingAs($this->teacher)->post(route('classes.writing.complete', [$this->group, $submission]))->assertNotFound();
    }

    public function test_learner_cannot_view_another_learners_submission_or_teacher_review_screen(): void
    {
        $submission = $this->createClassSubmission();

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('dashboard.writing.show', $submission))->assertNotFound();
        $this->actingAs($this->learner)
            ->get(route('classes.writing.show', [$this->group, $submission]))->assertForbidden();
    }

    public function test_teacher_feedback_revision_and_resubmission_preserve_versions_and_feedback(): void
    {
        $submission = $this->createClassSubmission();

        $this->actingAs($this->teacher)->post(route('classes.writing.begin-review', [$this->group, $submission]))
            ->assertRedirect();
        $this->assertSame('under_review', $submission->fresh()->status);

        $scores = [
            'task_achievement' => 3, 'organization' => 2, 'vocabulary' => 3,
            'grammar' => 2, 'mechanics' => 4,
        ];
        $this->actingAs($this->teacher)->post(route('classes.writing.feedback', [$this->group, $submission]), [
            'rubric_scores' => $scores,
            'overall_feedback' => 'Clear response. Add more supporting detail to the second point.',
            'strengths' => 'The purpose is clear.',
            'improvements' => 'Develop paragraph links.',
            'next_steps' => 'Revise the second paragraph and resubmit.',
        ])->assertRedirect();

        $this->assertSame('feedback_published', $submission->fresh()->status);
        $review = WritingSubmissionReview::query()->firstOrFail();
        $this->assertEqualsCanonicalizing($scores, $review->rubric_scores);
        $this->assertNotNull($review->published_at);

        $this->actingAs($this->learner)->get(route('dashboard.writing.show', $submission))
            ->assertOk()->assertSee('Clear response. Add more supporting detail');

        $this->actingAs($this->teacher)->post(route('classes.writing.request-revision', [$this->group, $submission]))
            ->assertRedirect();
        $this->assertSame('revision_requested', $submission->fresh()->status);

        $this->actingAs($this->learner)->post(route('dashboard.writing.revise', $submission), [
            'content' => 'A revised response with more detail and clearer paragraph links.',
            'status' => 'submitted',
        ])->assertRedirect();

        $submission->refresh();
        $this->assertSame('resubmitted', $submission->status);
        $this->assertSame(2, $submission->versions()->count());
        $this->assertSame(1, $submission->reviews()->count());
        $this->assertDatabaseHas('writing_submission_versions', [
            'writing_submission_id' => $submission->id,
            'version_number' => 2,
            'status' => 'resubmitted',
        ]);

        $this->actingAs($this->teacher)->post(route('classes.writing.begin-review', [$this->group, $submission]))->assertRedirect();
        $this->actingAs($this->teacher)->get(route('classes.writing.show', [$this->group, $submission]))
            ->assertOk()->assertSee('Clear response. Add more supporting detail');
        $this->actingAs($this->teacher)->post(route('classes.writing.feedback', [$this->group, $submission]), [
            'rubric_scores' => $scores,
            'overall_feedback' => 'The revision addresses the earlier feedback.',
        ])->assertRedirect();
        $this->actingAs($this->teacher)->post(route('classes.writing.complete', [$this->group, $submission]))->assertRedirect();

        $this->assertSame('completed', $submission->fresh()->status);
        $this->assertSame(2, $submission->reviews()->count());
        $this->actingAs($this->teacher)->get(route('classes.writing.show', [$this->group, $submission]))
            ->assertOk()->assertSee('Clear response. Add more supporting detail')->assertSee('The revision addresses the earlier feedback.');
        $this->actingAs($this->learner)->get(route('dashboard.writing.show', $submission))
            ->assertOk()->assertSee('Clear response. Add more supporting detail')->assertSee('The revision addresses the earlier feedback.');

        $learnerExport = app(PersonalDataExport::class)->for($this->learner);
        $this->assertCount(2, $learnerExport['records']['writing_submission_versions']);
        $this->assertCount(2, $learnerExport['records']['writing_submission_reviews']);
        $teacherExport = app(PersonalDataExport::class)->for($this->teacher);
        $this->assertCount(2, $teacherExport['records']['teacher_writing_reviews']);
    }

    public function test_invalid_state_transitions_and_incomplete_rubrics_are_rejected(): void
    {
        $submission = $this->createClassSubmission();

        $this->actingAs($this->teacher)->post(route('classes.writing.complete', [$this->group, $submission]))->assertSessionHasErrors('status');
        $this->actingAs($this->teacher)->post(route('classes.writing.feedback', [$this->group, $submission]), [
            'rubric_scores' => ['grammar' => 3], 'overall_feedback' => 'Feedback',
        ])->assertSessionHasErrors('rubric_scores.task_achievement');

        $this->actingAs($this->learner)->post(route('dashboard.writing.revise', $submission), [
            'content' => 'Attempt to change submitted work.', 'status' => 'submitted',
        ])->assertSessionHasErrors('content');
    }

    public function test_filament_submission_records_are_read_only_even_for_administrators(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->assertTrue(WritingSubmissionResource::canViewAny());
        $this->assertFalse(WritingSubmissionResource::canCreate());
        $this->assertFalse(WritingSubmissionResource::canDeleteAny());
    }

    public function test_class_and_assignment_records_cannot_be_deleted_while_submission_history_exists(): void
    {
        $this->createClassSubmission();

        try {
            $this->group->delete();
            $this->fail('A class with retained writing submissions must not be deleted.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('class_groups', ['id' => $this->group->id]);
        }

        try {
            $this->writingResource->writingResource->delete();
            $this->fail('A writing assignment with retained submissions must not be deleted.');
        } catch (QueryException) {
            $this->assertDatabaseHas('writing_resources', ['id' => $this->writingResource->writingResource->id]);
        }
    }

    public function test_unassigned_writing_resource_cannot_be_submitted_to_a_class(): void
    {
        $otherResource = Resource::query()->where('type', 'writing')->where('status', 'published')
            ->where('id', '!=', $this->writingResource->id)->first();
        if (! $otherResource) {
            $this->markTestSkipped('The fixture database contains only one published writing resource.');
        }

        $this->actingAs($this->learner)->post(route('classes.writing.submit', [$this->group, $otherResource]), [
            'content' => 'Not assigned.', 'status' => 'submitted',
        ])->assertNotFound();
    }

    private function createClassSubmission(): WritingSubmission
    {
        $this->actingAs($this->learner)->post(route('classes.writing.submit', [$this->group, $this->writingResource]), [
            'content' => 'This is a sample learner submission for review.', 'status' => 'submitted',
        ])->assertRedirect();

        return WritingSubmission::query()->firstOrFail();
    }
}
