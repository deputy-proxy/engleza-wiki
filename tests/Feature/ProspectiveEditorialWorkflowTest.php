<?php

namespace Tests\Feature;

use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectiveEditorialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_demo_content_is_grandfathered_without_being_retroactively_reviewed(): void
    {
        $resource = Resource::factory()->create([
            'editorial_status' => 'pending',
        ]);
        $translation = ContentTranslation::create([
            'translatable_type' => $resource->getMorphClass(),
            'translatable_id' => $resource->id,
            'locale' => 'ro',
            'field' => 'title',
            'value' => 'Traducere demo',
            'source_hash' => hash('sha256', $resource->title),
            'status' => 'reviewed',
        ]);

        $migration = require database_path('migrations/2026_11_19_000001_grandfather_existing_demo_content.php');
        $migration->up();

        $this->assertSame('grandfathered', $resource->fresh()->editorial_status);
        $this->assertNull($resource->fresh()->editorial_reviewed_by);
        $this->assertSame('grandfathered', $translation->fresh()->editorial_status);
        $this->assertTrue(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertOk();
        $this->assertSame('Traducere demo', $resource->fresh()->translated('title', 'ro'));
    }

    public function test_new_resource_requires_approval_before_publication(): void
    {
        $resource = Resource::factory()->create();

        $this->assertSame('pending', $resource->editorial_status);
        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertNotFound();

        $reviewer = User::factory()->create(['role' => 'admin']);
        $resource->approveEditorialReview($reviewer, 'Reviewed for the prospective workflow.');

        $this->assertSame('approved', $resource->fresh()->editorial_status);
        $this->assertSame($reviewer->id, $resource->fresh()->editorial_reviewed_by);
        $this->assertNotNull($resource->fresh()->editorial_reviewed_at);
        $this->assertTrue(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertOk();
    }

    public function test_new_assessment_requires_approval_before_it_is_listed_as_published(): void
    {
        $assessment = Assessment::factory()->create();

        $this->assertSame('pending', $assessment->editorial_status);
        $this->assertFalse(Assessment::published()->whereKey($assessment->id)->exists());

        $reviewer = User::factory()->create(['role' => 'admin']);
        $assessment->approveEditorialReview($reviewer);

        $this->assertTrue(Assessment::published()->whereKey($assessment->id)->exists());
    }

    public function test_new_learning_lesson_starts_pending(): void
    {
        $path = LearningPath::create([
            'title' => 'Issue 18 test path',
            'slug' => 'issue-18-test-path',
            'status' => 'draft',
        ]);
        $unit = LearningUnit::create([
            'learning_path_id' => $path->id,
            'title' => 'Issue 18 test unit',
            'sort_order' => 1,
        ]);
        $lesson = LearningLesson::create([
            'learning_unit_id' => $unit->id,
            'title' => 'Issue 18 test lesson',
            'slug' => 'issue-18-test-lesson',
            'status' => 'draft',
            'sort_order' => 1,
        ]);

        $this->assertSame('pending', $lesson->editorial_status);
    }

    public function test_rejected_new_content_remains_hidden_and_records_the_review_decision(): void
    {
        $resource = Resource::factory()->create();
        $reviewer = User::factory()->create(['role' => 'admin']);

        $resource->setEditorialDecision('revise', $reviewer, 'Add an explanation and verify the answer key.');

        $resource->refresh();
        $this->assertSame('revise', $resource->editorial_status);
        $this->assertSame($reviewer->id, $resource->editorial_reviewed_by);
        $this->assertSame('Add an explanation and verify the answer key.', $resource->editorial_notes);
        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
    }

    public function test_material_changes_to_newly_approved_content_invalidate_approval(): void
    {
        $resource = Resource::factory()->create();
        $reviewer = User::factory()->create(['role' => 'admin']);
        $resource->approveEditorialReview($reviewer);

        $resource->update(['content' => 'A new explanation that has not been reviewed.']);

        $this->assertSame('pending', $resource->fresh()->editorial_status);
        $this->assertNull($resource->fresh()->editorial_reviewed_by);
        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
    }

    public function test_new_translation_is_not_served_until_approved(): void
    {
        $resource = Resource::factory()->create(['title' => 'Present Simple']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $translation = ContentTranslation::create([
            'translatable_type' => $resource->getMorphClass(),
            'translatable_id' => $resource->id,
            'locale' => 'ro',
            'field' => 'title',
            'value' => 'Prezentul simplu',
            'source_hash' => hash('sha256', $resource->title),
            'status' => 'reviewed',
        ]);

        $this->assertSame('pending', $translation->editorial_status);
        $this->assertSame('Present Simple', $resource->translated('title', 'ro'));

        $translation->approveTranslation($reviewer);
        $resource->unsetRelation('localizedFields');

        $this->assertSame('Prezentul simplu', $resource->translated('title', 'ro'));
    }

    public function test_editing_a_grandfathered_demo_resource_moves_only_that_item_into_review(): void
    {
        $resource = Resource::factory()->create();
        $resource->forceFill(['editorial_status' => 'grandfathered'])->saveQuietly();

        $this->assertTrue(Resource::published()->whereKey($resource->id)->exists());

        $resource->update(['content' => 'A future edit to existing demo content.']);

        $this->assertSame('pending', $resource->fresh()->editorial_status);
        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
    }
}
