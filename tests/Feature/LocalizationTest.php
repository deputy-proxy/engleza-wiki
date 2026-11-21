<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\Resource;
use App\Models\Test as EnglishTest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->approveSeededContent();
    }

    public function test_public_pages_use_romanian_locale_and_localized_seo_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<html lang="ro"', false)
            ->assertSee('og:locale" content="ro_RO"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('hreflang="ro-RO"', false)
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
        $this->assertSame($sourceContent, $translatedContent);
        $this->assertSame('missing', $resource->translationStatus('content', 'ro'));
        $this->assertSame('reviewed', $resource->translationStatus('title', 'ro'));

        $response = $this->get(route('resources.show', $resource));
        $response->assertOk()
            ->assertSee($translatedTitle)
            ->assertSee('property="og:title" content="'.$resource->translated('seo_title').'"', false)
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

    public function test_writing_prompts_are_romanian_while_sample_english_material_is_preserved(): void
    {
        $resource = Resource::query()->where('type', 'writing')->where('status', 'published')->firstOrFail();
        $writing = $resource->writingResource;
        $sourcePrompt = $writing->prompt;
        $translatedPrompt = $writing->translated('prompt', 'ro');

        $this->assertNotSame($sourcePrompt, $translatedPrompt);
        $this->assertSame($sourcePrompt, $writing->prompt);

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee($translatedPrompt)
            ->assertDontSee($sourcePrompt);
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

    public function test_source_changes_invalidate_reviewed_content_translations_and_appear_in_the_audit(): void
    {
        $resource = Resource::query()->firstOrFail();
        $translation = ContentTranslation::query()
            ->where('translatable_type', $resource->getMorphClass())
            ->where('translatable_id', $resource->getKey())
            ->where('locale', 'ro')
            ->where('field', 'title')
            ->firstOrFail();

        self::assertSame(hash('sha256', $resource->title), $translation->source_hash);

        $resource->update(['title' => 'A changed English source title']);
        $resource->refresh();

        self::assertSame('A changed English source title', $resource->translated('title', 'ro'));
        self::assertSame('stale', $resource->translationStatus('title', 'ro'));
        $this->artisan('translations:audit')->assertExitCode(1);
    }

    public function test_every_literal_translation_call_in_public_code_has_a_romanian_json_entry(): void
    {
        $translations = json_decode(file_get_contents(lang_path('ro.json')), true, flags: JSON_THROW_ON_ERROR);
        $roots = [resource_path('views'), app_path('Http/Controllers'), app_path('Livewire'), app_path('Services')];
        $patterns = [
            "~__\\(\\s*'((?:\\\\.|[^'\\\\])*)'(?=\\s*[,\\)])~s",
            '~__\\(\\s*"((?:\\\\.|[^"\\\\])*)"(?=\\s*[,\\)])~s',
            "~@lang\\(\\s*'((?:\\\\.|[^'\\\\])*)'(?=\\s*[,\\)])~s",
            '~@lang\\(\\s*"((?:\\\\.|[^"\\\\])*)"(?=\\s*[,\\)])~s',
            "~trans_choice\\(\\s*'((?:\\\\.|[^'\\\\])*)'(?=\\s*[,\\)])~s",
        ];

        foreach ($roots as $root) {
            foreach (File::allFiles($root) as $file) {
                if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'Filament'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $contents = $file->getContents();
                foreach ($patterns as $pattern) {
                    preg_match_all($pattern, $contents, $matches);
                    foreach ($matches[1] as $key) {
                        $key = stripcslashes($key);
                        if (str_starts_with($key, 'validation.') || str_starts_with($key, 'auth.') || str_starts_with($key, 'passwords.') || str_starts_with($key, 'pagination.')) {
                            continue;
                        }
                        self::assertArrayHasKey($key, $translations, $file->getRelativePathname().' is missing a Romanian translation for: '.$key);
                    }
                }
            }
        }
    }

    public function test_translation_audit_flags_untranslated_long_form_content_until_it_is_reviewed(): void
    {
        $this->artisan('translations:audit', ['--locale' => 'ro'])->assertExitCode(1);
    }

    public function test_non_indexable_resources_are_noindex_and_excluded_from_the_sitemap(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $resource->update(['robots_index' => false]);
        $resource->approveEditorialReview(User::query()->where('role', 'admin')->firstOrFail());

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertDontSee(route('resources.show', $resource), false);
    }

    public function test_sitemap_lists_only_approved_public_content_and_non_empty_categories(): void
    {
        $reviewer = User::query()->where('role', 'admin')->firstOrFail();
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $test = EnglishTest::query()->where('status', 'published')->where('is_public', true)->firstOrFail();
        $category = Category::query()->create(['name' => 'Indexable test category', 'slug' => 'indexable-test-category', 'is_active' => true]);
        $emptyCategory = Category::query()->create(['name' => 'Empty test category', 'slug' => 'empty-test-category', 'is_active' => true]);

        $resource->categories()->attach($category);
        $resource->approveEditorialReview($reviewer);
        $test->approveEditorialReview($reviewer);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('resources.show', $resource), false)
            ->assertSee(route('tests.show', $test), false)
            ->assertSee(route('categories.show', $category), false)
            ->assertDontSee(route('categories.show', $emptyCategory), false)
            ->assertDontSee('/admin');
    }

    public function test_grandfathered_demo_resources_are_noindex_and_excluded_from_the_sitemap(): void
    {
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $resource->forceFill(['editorial_status' => 'grandfathered'])->saveQuietly();

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertDontSee(route('resources.show', $resource), false);
    }

    public function test_canonical_urls_use_app_url_and_drop_tracking_parameters(): void
    {
        config(['app.url' => 'https://engleza.wiki']);

        $this->get('https://engleza.wiki/resources?utm_source=campaign')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="https://engleza.wiki/resources">', false)
            ->assertSee('hreflang="ro-RO"', false)
            ->assertDontSee('x-default', false);
    }

    public function test_non_canonical_host_redirects_to_app_url_and_preserves_query_string(): void
    {
        config(['app.url' => 'https://engleza.wiki']);

        $this->get('https://www.engleza.wiki/resources?utm_source=campaign')
            ->assertRedirect('https://engleza.wiki/resources?utm_source=campaign');
    }

    public function test_filtered_resource_listings_are_noindex_and_canonicalize_to_the_clean_path(): void
    {
        $this->get(route('resources.index', ['type' => 'article']))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('<link rel="canonical" href="'.route('resources.index').'">', false);
    }

    public function test_approved_resource_emits_learning_resource_and_visible_breadcrumb_schema(): void
    {
        $reviewer = User::query()->where('role', 'admin')->firstOrFail();
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $resource->approveEditorialReview($reviewer);

        $response = $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"LearningResource"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('"inLanguage":"ro-RO"', false);

        preg_match_all('/<script type="application\\/ld\\+json">(.*?)<\\/script>/s', $response->getContent(), $matches);
        $this->assertCount(1, $matches[1]);

        $schemas = json_decode($matches[1][0], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['WebSite', 'LearningResource', 'BreadcrumbList'], array_column($schemas, '@type'));
        $this->assertArrayNotHasKey('aggregateRating', $schemas[1]);
        $this->assertArrayNotHasKey('review', $schemas[1]);
        $this->assertArrayNotHasKey('FAQPage', $schemas[1]);
    }

    public function test_external_resource_canonical_is_noindex_and_not_in_the_sitemap(): void
    {
        $reviewer = User::query()->where('role', 'admin')->firstOrFail();
        $resource = Resource::query()->where('status', 'published')->firstOrFail();
        $resource->update(['canonical_url' => 'https://example.org/english-resource']);
        $resource->approveEditorialReview($reviewer);

        $this->get(route('resources.show', $resource))
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertSee('<link rel="canonical" href="https://example.org/english-resource">', false)
            ->assertDontSee('hreflang=', false)
            ->assertDontSee('"@type":"LearningResource"', false);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertDontSee('https://example.org/english-resource', false);
    }

    public function test_public_listing_pages_with_unapproved_demo_content_are_noindex(): void
    {
        Resource::query()->where('status', 'published')->update(['editorial_status' => 'grandfathered']);
        EnglishTest::query()->where('status', 'published')->where('is_public', true)->update(['editorial_status' => 'grandfathered']);
        LearningLesson::query()->where('status', 'published')->update(['editorial_status' => 'grandfathered']);

        foreach (['resources.index', 'tests.index', 'learning.paths.index'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('name="robots" content="noindex,nofollow"', false);
        }
    }

    public function test_robots_and_rewrite_rules_protect_private_paths_without_blocking_assets(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $rootHtaccess = file_get_contents(base_path('.htaccess'));

        $this->assertIsString($robots);
        $this->assertStringContainsString('Disallow: /admin', $robots);
        $this->assertStringContainsString('Disallow: /dashboard', $robots);
        $this->assertStringContainsString('Sitemap: https://engleza.wiki/sitemap.xml', $robots);
        $this->assertStringNotContainsString('Disallow: /build', $robots);
        $this->assertStringNotContainsString('Disallow: /storage', $robots);

        $this->assertIsString($rootHtaccess);
        $this->assertStringContainsString('RewriteCond %{HTTP_HOST} ^www\\.engleza\\.wiki', $rootHtaccess);
        $this->assertStringContainsString('RewriteCond %{DOCUMENT_ROOT}/public%{REQUEST_URI} !-d', $rootHtaccess);
        $this->assertLessThan(
            strpos($rootHtaccess, 'RewriteRule ^(.*)$ public/$1 [L]'),
            strpos($rootHtaccess, 'RewriteRule ^(.+)/$ /$1 [R=301,L]'),
        );
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
