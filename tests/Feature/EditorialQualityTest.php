<?php

namespace Tests\Feature;

use App\Models\ContentTranslation;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\TestSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_resource_is_hidden_until_editorial_approval(): void
    {
        $resource = Resource::factory()->create();
        $reviewer = User::factory()->create(['role' => 'admin']);
        $resource->setEditorialDecision('revise', $reviewer, 'Needs a complete explanation and answer key.');

        $this->assertDatabaseHas('resources', ['id' => $resource->id, 'status' => 'published', 'editorial_status' => 'revise']);
        $this->assertFalse(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertNotFound();

        $resource->approveEditorialReview($reviewer, 'Teacher review completed.');

        $this->assertTrue(Resource::published()->whereKey($resource->id)->exists());
        $this->get(route('resources.show', $resource))->assertOk();
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'editorial_status' => 'approved',
            'editorial_reviewed_by' => $reviewer->id,
        ]);
    }

    public function test_editing_reviewed_source_content_invalidates_approval_and_public_visibility(): void
    {
        $resource = Resource::factory()->create();
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $this->assertTrue($resource->isEditorialApproved());

        $resource->update(['content' => 'A materially changed explanation.']);
        $resource->refresh();

        $this->assertSame('pending', $resource->editorial_status);
        $this->assertNull($resource->editorial_reviewed_by);
        $this->get(route('resources.show', $resource))->assertNotFound();
    }

    public function test_changes_to_linked_questions_and_options_invalidate_parent_review(): void
    {
        $test = Assessment::factory()->create();
        $section = TestSection::create(['test_id' => $test->id, 'title' => 'Grammar', 'sort_order' => 1]);
        $question = Question::create([
            'test_section_id' => $section->id,
            'type' => 'single_choice',
            'question' => 'Which form is correct?',
            'explanation' => 'The third-person singular form takes -s.',
            'points' => 1,
            'difficulty' => 'easy',
            'sort_order' => 1,
        ]);
        $option = QuestionOption::create([
            'question_id' => $question->id, 'label' => 'A', 'value' => 'works', 'is_correct' => true, 'sort_order' => 1,
        ]);
        QuestionOption::create([
            'question_id' => $question->id, 'label' => 'B', 'value' => 'work', 'is_correct' => false, 'sort_order' => 2,
        ]);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $test->refresh()->approveEditorialReview($reviewer);

        $option->update(['value' => 'work']);

        $this->assertSame('pending', $test->fresh()->editorial_status);
        $this->assertNull($test->fresh()->editorial_reviewed_at);
    }

    public function test_changes_to_linked_questions_invalidate_the_parent_resource_review(): void
    {
        $resource = Resource::factory()->create(['type' => 'listening']);
        $listening = ListeningResource::create([
            'resource_id' => $resource->id,
            'instructions' => 'Listen and answer.',
        ]);
        $question = Question::create([
            'listening_resource_id' => $listening->id,
            'type' => 'single_choice',
            'question' => 'What time does the train leave?',
            'explanation' => 'The speaker gives the departure time.',
            'points' => 1,
            'difficulty' => 'easy',
            'sort_order' => 1,
        ]);
        $option = QuestionOption::create([
            'question_id' => $question->id, 'label' => 'A', 'value' => 'At nine', 'is_correct' => true, 'sort_order' => 1,
        ]);
        QuestionOption::create([
            'question_id' => $question->id, 'label' => 'B', 'value' => 'At ten', 'is_correct' => false, 'sort_order' => 2,
        ]);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $resource->refresh()->approveEditorialReview($reviewer);

        $option->update(['value' => 'At eleven']);

        $this->assertSame('pending', $resource->fresh()->editorial_status);
        $this->get(route('resources.show', $resource))->assertNotFound();
    }

    public function test_translation_is_only_served_after_editorial_approval_and_current_source_hash(): void
    {
        $resource = Resource::factory()->create(['title' => 'Understanding the Present Simple']);
        $reviewer = User::factory()->create(['role' => 'admin']);
        $translation = ContentTranslation::create([
            'translatable_type' => $resource->getMorphClass(),
            'translatable_id' => $resource->id,
            'locale' => 'ro',
            'field' => 'title',
            'value' => 'Înțelegerea timpului Present Simple',
            'source_hash' => hash('sha256', $resource->title),
            'status' => 'reviewed',
        ]);

        $this->assertSame($resource->title, $resource->translated('title', 'ro'));
        $this->assertSame('editorial_pending', $resource->translationStatus('title', 'ro'));

        $translation->approveTranslation($reviewer);
        $resource->approveEditorialReview($reviewer);
        $resource->unsetRelation('localizedFields');

        $this->assertSame('Înțelegerea timpului Present Simple', $resource->translated('title', 'ro'));

        $resource->update(['title' => 'Present Simple: form and use']);
        $resource->unsetRelation('localizedFields');

        $this->assertSame('Present Simple: form and use', $resource->translated('title', 'ro'));
        $this->assertSame('stale', $resource->translationStatus('title', 'ro'));
    }

    public function test_audit_command_exports_published_resources_and_tests_with_review_dispositions(): void
    {
        Resource::factory()->create(['title' => 'Editorial audit sample']);
        $legacy = Resource::factory()->create(['title' => 'Legacy demo content excluded']);
        $legacy->forceFill(['editorial_status' => 'grandfathered'])->saveQuietly();
        Assessment::factory()->create(['title' => 'Editorial test sample']);
        $path = storage_path('framework/testing/editorial-audit.csv');

        $this->artisan('content:editorial-audit', ['--output' => $path])->assertExitCode(0);

        $this->assertFileExists($path);
        $csv = file_get_contents($path);
        $this->assertIsString($csv);
        $this->assertStringContainsString('Editorial audit sample', $csv);
        $this->assertStringContainsString('Editorial test sample', $csv);
        $this->assertStringNotContainsString('Legacy demo content excluded', $csv);
        $this->assertStringContainsString('recommended_decision', $csv);
    }

    public function test_noindex_decision_is_rejected_for_content_without_indexing_controls(): void
    {
        $test = Assessment::factory()->create();
        $reviewer = User::factory()->create(['role' => 'admin']);

        $this->expectException(\InvalidArgumentException::class);
        $test->setEditorialDecision('noindex', $reviewer, 'Tests do not have a per-record indexing control.');
    }

    public function test_admin_can_manage_translation_records_in_the_existing_filament_panel(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/content-translations')->assertOk();
    }
}
