<?php

namespace App\Console\Commands;

use App\Models\Audience;
use App\Models\CardResource;
use App\Models\Category;
use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\Level;
use App\Models\ListeningResource;
use App\Models\Question;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\Skill;
use App\Models\Tag;
use App\Models\Test;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\WritingResource;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

class AuditTranslations extends Command
{
    protected $signature = 'translations:audit {--locale=ro : Locale to audit}';

    protected $description = 'List published content fields with missing or unreviewed translations.';

    /** @var array<int, array{type: string, id: int|string, title: string, field: string, status: string, source: string, translation: string}> */
    private array $issues = [];

    public function handle(): int
    {
        $locale = (string) $this->option('locale');

        $this->audit(Resource::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->get(), ['title', 'excerpt', 'content', 'seo_title', 'seo_description', 'og_title', 'og_description'], $locale);
        $this->audit(Test::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->get(), ['title', 'description', 'instructions'], $locale);
        $this->audit(TestSection::query()->whereHas('test', fn ($query) => $query->where('status', 'published')->where('editorial_status', '!=', 'grandfathered'))->get(), ['title', 'description', 'instructions'], $locale);
        $this->audit(Question::query()->where(function ($query): void {
            $query->whereHas('section.test', fn ($test) => $test->where('status', 'published')->where('editorial_status', '!=', 'grandfathered'))
                ->orWhereHas('listeningResource.resource', fn ($resource) => $resource->where('status', 'published')->where('editorial_status', '!=', 'grandfathered'))
                ->orWhereHas('readingResource.resource', fn ($resource) => $resource->where('status', 'published'));
        })->get(), ['explanation'], $locale);
        $publishedResourceIds = Resource::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->pluck('id');
        $this->audit(CardResource::query()->whereIn('resource_id', $publishedResourceIds)->get(), ['instructions'], $locale);
        $this->audit(ListeningResource::query()->whereIn('resource_id', $publishedResourceIds)->get(), ['instructions'], $locale);
        $this->audit(ReadingResource::query()->whereIn('resource_id', $publishedResourceIds)->get(), ['title', 'instructions'], $locale);
        $this->audit(WritingResource::query()->whereIn('resource_id', $publishedResourceIds)->get(), ['prompt', 'instructions', 'rubric'], $locale);
        $this->audit(LearningPath::query()->where('status', 'published')->get(), ['title', 'description'], $locale);
        $this->audit(LearningUnit::query()->where('is_published', true)->whereHas('path', fn ($query) => $query->where('status', 'published'))->get(), ['title', 'description'], $locale);
        $this->audit(LearningLesson::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->whereHas('unit.path', fn ($query) => $query->where('status', 'published'))->get(), ['title', 'description', 'objectives'], $locale);
        $this->audit(LessonActivity::query()->whereHas('lesson', fn ($query) => $query->where('status', 'published')->where('editorial_status', '!=', 'grandfathered'))->get(), ['title', 'instructions'], $locale);

        if ($this->issues === []) {
            $this->info("All published {$locale} content fields have reviewed translations.");

            return self::SUCCESS;
        }

        $this->table(['Type', 'ID', 'Record', 'Field', 'Status', 'Source text', 'Romanian text'], array_map(
            fn (array $issue): array => [$issue['type'], $issue['id'], $issue['title'], $issue['field'], $issue['status'], $issue['source'], $issue['translation']],
            $this->issues,
        ));
        $this->error(count($this->issues).' published content translation(s) are missing or not reviewed.');

        return self::FAILURE;
    }

    /**
     * @param  iterable<int, Model>  $models
     * @param  array<int, string>  $fields
     */
    private function audit(iterable $models, array $fields, string $locale): void
    {
        foreach ($models as $model) {
            foreach ($fields as $field) {
                if ($model->getAttribute($field) === null || $model->getAttribute($field) === '') {
                    continue;
                }

                $translation = ContentTranslation::query()
                    ->where('translatable_type', $model->getMorphClass())
                    ->where('translatable_id', $model->getKey())
                    ->where('locale', $locale)
                    ->where('field', $field)
                    ->first(['status', 'source_hash', 'value', 'editorial_status']);
                $source = $model->getAttribute($field);
                $sourceValue = is_array($source)
                    ? json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                    : (string) $source;
                $sourceHash = hash('sha256', $sourceValue);
                $status = $translation->status ?? 'missing';
                if ($status === 'reviewed' && ! hash_equals((string) $translation->source_hash, $sourceHash)) {
                    $status = 'stale';
                } elseif ($status === 'reviewed' && $translation->editorial_status !== 'approved') {
                    $status = 'editorial_pending';
                }

                if ($status !== 'reviewed') {
                    $this->issues[] = [
                        'type' => class_basename($model),
                        'id' => $model->getKey(),
                        'title' => (string) ($model->getAttribute('title') ?? $model->getAttribute('name') ?? $model->getAttribute('slug') ?? ''),
                        'field' => $field,
                        'status' => $status,
                        'source' => mb_strimwidth(strip_tags($sourceValue), 0, 120, '…'),
                        'translation' => mb_strimwidth(strip_tags((string) ($translation->value ?? '')), 0, 120, '…'),
                    ];
                }
            }
        }
    }
}
