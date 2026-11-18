<?php

namespace Tests\Feature;

use App\Models\ContentTranslation;
use App\Models\Resource;
use App\Models\Test as EnglishTest;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_public_pages_use_romanian_locale_and_localized_seo_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ro"', false)
            ->assertSee('og:locale" content="ro_RO"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('hreflang="ro"', false)
            ->assertSee('Acasă')
            ->assertSee('Explorează resursele');
    }

    public function test_resource_translations_are_stored_separately_from_english_source_content(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $sourceTitle = $resource->title;
        $sourceContent = $resource->content;
        $translatedTitle = $resource->translated('title', 'ro');
        $translatedContent = $resource->translated('content', 'ro');

        $this->assertNotSame($sourceTitle, $translatedTitle);
        $this->assertSame($sourceTitle, $resource->translated('title', 'en'));
        $this->assertSame($sourceContent, $resource->content);
        $this->assertNotSame($sourceContent, $translatedContent);
        $this->assertStringContainsString('She **works** in a hospital.', $translatedContent);
        $this->assertSame('reviewed', $resource->translationStatus('title', 'ro'));

        $response = $this->get(route('resources.show', $resource));
        $response->assertOk()
            ->assertSee($translatedTitle)
            ->assertSee('property="og:title" content="'.$resource->translated('og_title').'"', false)
            ->assertSee('name="description" content="'.$resource->translated('seo_description').'"', false);
    }

    public function test_search_matches_romanian_resource_titles(): void
    {
        $resource = Resource::query()->where('slug', 'understanding-the-present-simple')->firstOrFail();
        $translatedTitle = $resource->translated('title', 'ro');
        $term = mb_substr($translatedTitle, 0, 10);

        $this->get(route('search', ['q' => $term]))
            ->assertOk()
            ->assertSee($translatedTitle);
    }

    public function test_interactive_resource_instructions_are_romanian_and_learning_passage_stays_english(): void
    {
        $resource = Resource::query()->where('type', 'reading')->where('status', 'published')->firstOrFail();
        $reading = $resource->readingResource;
        $sourcePassage = $reading->content;
        $instructions = $reading->translated('instructions', 'ro');

        $this->assertNotSame($reading->instructions, $instructions);
        $this->assertSame($sourcePassage, $reading->content);

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee($instructions)
            ->assertSee($sourcePassage);
    }

    public function test_test_questions_and_answer_options_remain_unchanged_learning_material(): void
    {
        $test = EnglishTest::query()->where('status', 'published')->firstOrFail();
        $question = $test->allQuestions()->firstOrFail();
        $option = $question->options()->firstOrFail();

        $this->assertSame($question->question, $question->getOriginal('question'));
        $this->assertSame($option->value, $option->getOriginal('value'));
        $this->assertSame($test->title, $test->translated('title', 'en'));
        $this->assertNotSame($test->title, $test->translated('title', 'ro'));
    }

    public function test_translation_audit_passes_for_all_published_content(): void
    {
        $this->artisan('translations:audit', ['--locale' => 'ro'])
            ->expectsOutput('All published ro content fields have reviewed translations.')
            ->assertExitCode(0);
    }

    public function test_sitemap_lists_public_content_without_admin_routes(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $test = EnglishTest::query()->where('status', 'published')->where('is_public', true)->firstOrFail();

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('resources.show', $resource), false)
            ->assertSee(route('tests.show', $test), false)
            ->assertDontSee('/admin');
    }

    public function test_validation_messages_are_in_romanian(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => 'required|email'],
        );

        $this->assertSame(
            'Câmpul adresă de e-mail trebuie să conțină o adresă de e-mail validă.',
            $validator->errors()->first('email'),
        );
    }

    public function test_public_not_found_page_is_localized(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Pagina nu a fost găsită');
    }

    public function test_draft_translations_fall_back_to_source_and_fail_the_audit(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();

        ContentTranslation::query()
            ->where('translatable_type', $resource->getMorphClass())
            ->where('translatable_id', $resource->getKey())
            ->where('locale', 'ro')
            ->where('field', 'title')
            ->update(['status' => 'draft']);

        $resource->refresh();

        $this->assertSame($resource->title, $resource->translated('title', 'ro'));
        $this->assertSame('draft', $resource->translationStatus('title', 'ro'));
        $this->artisan('translations:audit', ['--locale' => 'ro'])->assertExitCode(1);
    }

    public function test_untranslated_fields_fall_back_to_source_content(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();

        $this->assertSame($resource->slug, $resource->translated('slug', 'ro'));
        $this->assertSame($resource->title, $resource->translated('title', 'en'));
    }
}
