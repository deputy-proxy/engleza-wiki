<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\ClassGroupMembership;
use App\Models\ClassGroupTransfer;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\TestAttempt;
use App\Models\User;
use App\Models\WritingResource;
use App\Services\Learning\ClassGroupService;
use App\Services\Learning\LearnerProgressService;
use App\Services\Privacy\PersonalDataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherClassWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private User $otherTeacher;

    private User $learner;

    private ClassGroup $group;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->approveSeededContent();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->otherTeacher = User::factory()->create(['role' => 'teacher']);
        $this->learner = User::factory()->create(['role' => 'learner']);
        $this->group = ClassGroup::query()->create(['name' => 'A1 evening class', 'join_code' => 'TEACH1', 'teacher_id' => $this->teacher->id]);
    }

    public function test_teacher_workspace_is_role_and_ownership_scoped(): void
    {
        app(ClassGroupService::class)->join($this->learner, $this->group);
        $this->actingAs($this->teacher)->get(route('teaching.show', $this->group))->assertOk()->assertSee($this->learner->name)->assertDontSee($this->learner->email);
        $this->actingAs($this->teacher)->get(route('teaching.index'))->assertOk()->assertSee('A1 evening class');
        $this->actingAs($this->otherTeacher)->get(route('teaching.index'))->assertOk()->assertDontSee('A1 evening class');
        $this->actingAs($this->otherTeacher)->get(route('teaching.show', $this->group))->assertForbidden();
        $this->actingAs($this->learner)->get(route('teaching.index'))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('teaching.show', $this->group))->assertOk();
    }

    public function test_teacher_can_create_class_and_assign_only_published_approved_content(): void
    {
        $test = Assessment::factory()->create();
        $test->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $resource = Resource::factory()->create(['type' => 'writing']);
        $writing = WritingResource::query()->create(['resource_id' => $resource->id, 'prompt' => 'Write a short description.']);
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'editorial_status' => 'approved']);
        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'type' => 'writing', 'status' => 'published', 'editorial_status' => 'approved']);
        $this->assertTrue(WritingResource::query()->whereKey($writing->id)->whereHas('resource', fn ($q) => $q->where('status', 'published')->where('editorial_status', 'approved')->where('type', 'writing'))->exists());

        $this->actingAs($this->teacher)->post(route('teaching.store'), ['name' => 'New grammar class', 'description' => 'Description'])
            ->assertRedirect();
        $created = ClassGroup::query()->where('name', 'New grammar class')->firstOrFail();
        $this->assertSame($this->teacher->id, $created->teacher_id);
        $this->assertNotEmpty($created->join_code);

        $this->actingAs($this->teacher)->put(route('teaching.update', $created), [
            'name' => 'New grammar class', 'description' => 'Updated description', 'test_id' => $test->id,
            'writing_resource_ids' => [$writing->id],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame($test->id, $created->fresh()->test_id);
        $this->assertDatabaseHas('class_group_writing_resource', ['class_group_id' => $created->id, 'writing_resource_id' => $writing->id]);

        $draft = Assessment::factory()->create(['status' => 'draft']);
        $this->actingAs($this->teacher)->put(route('teaching.update', $created), [
            'name' => 'New grammar class', 'test_id' => $draft->id,
        ])->assertSessionHasErrors('test_id');
    }

    public function test_administrator_can_create_a_class_and_assign_its_teacher(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('teaching.create'))->assertOk()->assertSee('Atribuie profesor');
        $this->actingAs($admin)->post(route('teaching.store'), [
            'name' => 'Admin-created class', 'teacher_id' => $this->otherTeacher->id,
        ])->assertRedirect();
        $this->assertDatabaseHas('class_groups', [
            'name' => 'Admin-created class', 'teacher_id' => $this->otherTeacher->id,
        ]);
        $this->actingAs($this->otherTeacher)->get(route('teaching.index'))->assertOk()->assertSee('Admin-created class');
    }

    public function test_learners_join_explicitly_and_duplicate_join_is_idempotent(): void
    {
        $this->actingAs($this->learner)->get(route('classes.show', $this->group))->assertOk()->assertSee('Înscrie-te în această clasă');
        $this->actingAs($this->learner)->post(route('classes.join', $this->group->join_code))->assertRedirect();
        $this->actingAs($this->learner)->post(route('classes.join', strtolower($this->group->join_code)))->assertRedirect();
        $this->assertSame(1, ClassGroupMembership::query()->where('class_group_id', $this->group->id)->where('user_id', $this->learner->id)->whereNull('removed_at')->count());
        $this->actingAs($this->learner)->get(route('classes.show', $this->group))->assertOk()->assertSee('Ești membru al acestei clase.');
        $this->actingAs($this->teacher)->post(route('classes.join', $this->group->join_code))->assertForbidden();
    }

    public function test_teacher_can_add_and_remove_learners_without_deleting_membership_history(): void
    {
        $this->actingAs($this->teacher)->post(route('teaching.members.add', $this->group), ['email' => $this->learner->email])->assertRedirect();
        $membership = ClassGroupMembership::query()->where('class_group_id', $this->group->id)->firstOrFail();
        $this->assertSame($this->teacher->id, $membership->added_by);
        $this->actingAs($this->teacher)->delete(route('teaching.members.remove', [$this->group, $membership]))->assertRedirect();
        $membership->refresh();
        $this->assertNotNull($membership->removed_at);
        $this->assertSame($this->teacher->id, $membership->removed_by);
        try {
            $membership->delete();
            $this->fail('Membership history must not be deletable.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('class_group_memberships', ['id' => $membership->id]);
        }
        $this->actingAs($this->teacher)->get(route('teaching.show', $this->group))->assertOk()->assertDontSee($this->learner->name);
        $this->actingAs($this->otherTeacher)->delete(route('teaching.members.remove', [$this->group, $membership]))->assertForbidden();
    }

    public function test_removed_learners_are_excluded_from_current_results_but_history_is_retained(): void
    {
        $membership = app(ClassGroupService::class)->join($this->learner, $this->group);
        $test = Assessment::factory()->create();
        TestAttempt::query()->create(['test_id' => $test->id, 'user_id' => $this->learner->id, 'class_group_id' => $this->group->id, 'started_at' => now()->subHour(), 'completed_at' => now(), 'status' => 'completed', 'percentage' => 82, 'score' => 8.2]);
        $this->assertCount(1, app(LearnerProgressService::class)->classGroupSummary($this->group));
        app(ClassGroupService::class)->remove($this->group, $membership, $this->teacher);
        $this->assertCount(0, app(LearnerProgressService::class)->classGroupSummary($this->group));
        $this->actingAs($this->teacher)->get(route('classes.results', $this->group->join_code))->assertOk()->assertDontSee($this->learner->name)->assertDontSee('82%');
        $this->assertDatabaseHas('test_attempts', ['class_group_id' => $this->group->id, 'user_id' => $this->learner->id]);
    }

    public function test_a_learner_can_belong_to_multiple_classes_without_cross_class_visibility(): void
    {
        $secondGroup = ClassGroup::query()->create(['name' => 'B1 weekend class', 'join_code' => 'TEACH2', 'teacher_id' => $this->otherTeacher->id]);
        $firstMembership = app(ClassGroupService::class)->join($this->learner, $this->group);
        app(ClassGroupService::class)->join($this->learner, $secondGroup);
        $test = Assessment::factory()->create();
        foreach ([$this->group, $secondGroup] as $group) {
            TestAttempt::query()->create([
                'test_id' => $test->id, 'user_id' => $this->learner->id, 'class_group_id' => $group->id,
                'started_at' => now()->subHour(), 'completed_at' => now(), 'status' => 'completed', 'percentage' => 76,
            ]);
        }
        $this->actingAs($this->teacher)->delete(route('teaching.members.remove', [$this->group, $firstMembership]))->assertRedirect();
        $this->assertCount(0, app(LearnerProgressService::class)->classGroupSummary($this->group));
        $this->assertCount(1, app(LearnerProgressService::class)->classGroupSummary($secondGroup));
        $secondMembership = ClassGroupMembership::query()->where('class_group_id', $secondGroup->id)->firstOrFail();
        $this->actingAs($this->teacher)->delete(route('teaching.members.remove', [$this->group, $secondMembership]))->assertNotFound();
        $this->assertNull($secondMembership->fresh()->removed_at);
        $this->actingAs($this->teacher)->get(route('classes.results', $this->group->join_code))->assertOk()->assertDontSee($this->learner->name);
        $this->actingAs($this->otherTeacher)->get(route('classes.results', $secondGroup->join_code))->assertOk()->assertSee($this->learner->name);
        $teacherExport = app(PersonalDataExport::class)->for($this->teacher);
        $this->assertSame([$this->group->id], collect($teacherExport['records']['teaching_class_groups'])->pluck('id')->all());
    }

    public function test_class_test_attempt_requires_membership_and_the_assigned_test(): void
    {
        $test = Assessment::factory()->create();
        $test->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $this->group->update(['test_id' => $test->id]);
        $this->actingAs($this->learner)->post(route('tests.start', $test), ['class_code' => $this->group->join_code])->assertForbidden();
        app(ClassGroupService::class)->join($this->learner, $this->group);
        $this->actingAs($this->learner)->post(route('tests.start', $test), ['class_code' => $this->group->join_code])->assertRedirect();
        $this->assertDatabaseHas('test_attempts', ['user_id' => $this->learner->id, 'class_group_id' => $this->group->id, 'test_id' => $test->id]);
        $other = Assessment::factory()->create();
        $other->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $this->actingAs($this->learner)->post(route('tests.start', $other), ['class_code' => $this->group->join_code])->assertForbidden();
    }

    public function test_transfer_is_audited_and_revokes_previous_teacher_access(): void
    {
        $this->actingAs($this->teacher)->post(route('teaching.transfer', $this->group), ['teacher_id' => $this->otherTeacher->id])->assertRedirect(route('teaching.index'));
        $this->assertSame($this->otherTeacher->id, $this->group->fresh()->teacher_id);
        $this->assertDatabaseHas('class_group_transfers', ['class_group_id' => $this->group->id, 'previous_teacher_id' => $this->teacher->id, 'new_teacher_id' => $this->otherTeacher->id, 'transferred_by' => $this->teacher->id]);
        $this->actingAs($this->teacher)->get(route('teaching.show', $this->group))->assertForbidden();
        $this->actingAs($this->otherTeacher)->get(route('teaching.show', $this->group))->assertOk();
        try {
            ClassGroupTransfer::query()->firstOrFail()->delete();
            $this->fail('Transfer history must not be deletable.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('class_group_transfers', ['class_group_id' => $this->group->id]);
        }
    }

    public function test_archiving_stops_new_joins_and_class_groups_cannot_be_deleted(): void
    {
        try {
            $this->group->delete();
            $this->fail('Class groups must be archived, not deleted.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('class_groups', ['id' => $this->group->id]);
        }
        $this->actingAs($this->teacher)->post(route('teaching.toggle-active', $this->group))->assertRedirect();
        $this->assertFalse($this->group->fresh()->is_active);
        $this->get(route('classes.show', $this->group))->assertNotFound();
        $this->actingAs($this->teacher)->post(route('teaching.toggle-active', $this->group))->assertRedirect();
        $this->assertTrue($this->group->fresh()->is_active);
    }

    public function test_personal_data_export_includes_own_membership_and_teacher_class_events_without_other_learner_ids(): void
    {
        app(ClassGroupService::class)->join($this->learner, $this->group, $this->teacher);
        $learnerExport = app(PersonalDataExport::class)->for($this->learner);
        $this->assertCount(1, $learnerExport['records']['class_group_memberships']);
        $teacherExport = app(PersonalDataExport::class)->for($this->teacher);
        $this->assertCount(1, $teacherExport['records']['teaching_class_membership_events']);
        $this->assertArrayNotHasKey('user_id', $teacherExport['records']['teaching_class_membership_events'][0]);
        $this->assertCount(1, $teacherExport['records']['class_group_membership_actions']);
    }
}
