<?php

namespace App\Console\Commands;

use App\Models\ContentTranslation;
use App\Models\LearningLesson;
use App\Models\Question;
use App\Models\Resource;
use App\Models\Test;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class EditorialAudit extends Command
{
    protected $signature = 'content:editorial-audit {--output= : Write the complete CSV register to this path}';

    protected $description = 'Inventory content created under the editorial workflow, including translation state and review findings.';

    /** @var array<int, array<string, string|int|null>> */
    private array $rows = [];

    public function handle(): int
    {
        $this->auditResources();
        $this->auditTests();
        $this->auditLessons();
        $this->auditTranslations();

        $counts = collect($this->rows)->countBy('record_type');
        $this->table(['Record type', 'Count'], $counts->map(fn (int $count, string $type): array => [$type, $count])->values()->all());

        $output = $this->option('output');
        if (is_string($output) && $output !== '') {
            $path = str_starts_with($output, DIRECTORY_SEPARATOR) ? $output : base_path($output);
            File::ensureDirectoryExists(dirname($path));
            $stream = fopen($path, 'wb');
            if ($stream === false) {
                $this->error("Unable to open audit register for writing: {$path}");

                return self::FAILURE;
            }

            fputcsv($stream, array_keys($this->rows[0] ?? $this->headers()));
            foreach ($this->rows as $row) {
                fputcsv($stream, array_values($row));
            }
            fclose($stream);
            $this->info('Editorial audit register written to '.$path);
        }

        $pending = collect($this->rows)->filter(fn (array $row): bool => ($row['editorial_status'] ?? 'pending') !== 'approved')->count();
        $this->line(count($this->rows).' inventory records; '.$pending.' require editorial approval or a documented disposition.');

        return self::SUCCESS;
    }

    private function auditResources(): void
    {
        Resource::query()->where('editorial_status', '!=', 'grandfathered')->whereIn('status', ['published', 'draft'])->with([
            'level', 'skills', 'topics', 'media', 'listeningResource.questions.options',
            'readingResource.questions.options', 'writingResource', 'speakingResource', 'cardResource.cards',
        ])->orderBy('id')->each(function (Resource $resource): void {
            $notes = [];
            $recommendation = 'revise';
            $media = 'none';

            if ($resource->type === 'listening') {
                $listening = $resource->listeningResource;
                $media = $listening?->media_path ? 'audio/video file attached; rights metadata must be checked' : 'missing audio file';
                if (! $listening?->media_path) {
                    $recommendation = 'unpublish';
                    $notes[] = 'Listening exercise has questions but no playable audio source.';
                } elseif (blank($listening->media_license) || blank($listening->media_attribution) || (strtolower((string) $listening->media_license) !== 'original recording' && blank($listening->media_source_url) && blank($listening->media_permission_reference))) {
                    $recommendation = 'unpublish';
                    $notes[] = 'Audio is attached but source, licence/permission basis or attribution evidence is incomplete.';
                }
                if (($listening?->questions->count() ?? 0) === 0) {
                    $recommendation = 'unpublish';
                    $notes[] = 'No listening questions are attached.';
                }
            } elseif ($resource->type === 'reading') {
                $reading = $resource->readingResource;
                $wordCount = str_word_count((string) $reading?->content);
                $questionCount = $reading?->questions->count() ?? 0;
                $notes[] = "Reading passage: {$wordCount} words; {$questionCount} questions. Teacher review of level and answer key remains required.";
                if ($wordCount < 100 || $questionCount < 3) {
                    $notes[] = 'Passage length or question count is below the editorial baseline.';
                }
            } elseif ($resource->type === 'writing') {
                $writing = $resource->writingResource;
                $notes[] = $writing?->rubric ? 'Writing rubric supplied; assess criteria and model response.' : 'Writing task has no assessment rubric.';
                if (! $writing?->rubric) {
                    $notes[] = 'Add task-specific assessment criteria before publication.';
                }
            } elseif ($resource->type === 'speaking') {
                $speaking = $resource->speakingResource;
                $issues = $speaking?->publicationIssues() ?? ['A structured speaking task is missing.'];
                $notes[] = $issues === []
                    ? 'Structured speaking task supplied. Verify CEFR fit, target-skill alignment, instructions and self-check criteria; no automated score is used.'
                    : implode(' ', $issues);
                if ($issues !== []) {
                    $recommendation = 'unpublish';
                }
            } elseif ($resource->type === 'card') {
                $count = $resource->cardResource?->cards->count() ?? 0;
                $notes[] = "Flashcard count: {$count}. Review definition precision, examples and distractor-free answers.";
            } else {
                $content = (string) $resource->content;
                $boilerplate = [
                    'This resource is part of engleza.wiki',
                    'Concept clearly explained',
                    'Multiple examples',
                    'Practice exercises',
                    'Answer key',
                ];
                $matches = array_filter($boilerplate, fn (string $phrase): bool => str_contains($content, $phrase));
                if (count($matches) >= 3 || mb_strlen(trim($content)) < 700) {
                    $notes[] = 'Contains generic template copy or lacks a complete explanation, worked examples, meaningful practice and explained answers.';
                }
            }

            if ($resource->level === null || $resource->skills->isEmpty() || $resource->topics->isEmpty()) {
                $notes[] = 'CEFR level, skill or topic taxonomy is incomplete.';
            }
            if (! $resource->seo_title || ! $resource->seo_description) {
                $notes[] = 'SEO title or description is missing.';
            }
            if (! $resource->canonical_url) {
                $notes[] = 'Canonical URL uses the route-derived fallback; verify it resolves to the preferred URL.';
            }
            if ($resource->media->isNotEmpty()) {
                $rightsIncomplete = $resource->media->contains(fn ($item): bool => blank($item->license) || blank($item->attribution) || (strtolower((string) $item->license) !== 'original recording' && blank($item->source_url) && blank($item->permission_reference)));
                $notes[] = $rightsIncomplete ? 'One or more media assets lack source, licence/permission basis or attribution evidence.' : 'Media rights metadata is recorded; verify the evidence and actual licence terms.';
                if ($rightsIncomplete) {
                    $recommendation = 'unpublish';
                }
                $media = $rightsIncomplete ? 'media attached; rights metadata incomplete' : 'media attached; rights metadata recorded';
            }
            if ($resource->editorial_status === 'noindex') {
                $recommendation = 'noindex';
            }

            $this->addRow('Resource', $resource, $recommendation, $media, implode(' ', $notes), [
                'cefr' => $resource->level?->name,
                'skills' => $resource->skills->pluck('name')->implode('; '),
                'topics' => $resource->topics->pluck('name')->implode('; '),
                'translation' => $resource->translationStatus('title', 'ro'),
                'seo' => $resource->seo_title && $resource->seo_description ? 'present' : 'incomplete',
            ]);

            foreach ([
                'Listening item' => $resource->listeningResource,
                'Reading item' => $resource->readingResource,
                'Writing item' => $resource->writingResource,
                'Speaking item' => $resource->speakingResource,
                'Flashcard set' => $resource->cardResource,
            ] as $type => $child) {
                if ($child !== null) {
                    $this->addRow($type, $child, $recommendation, $media, 'Review the complete child content and its translation; the parent resource controls publication.', [
                        'parent_slug' => $resource->slug,
                        'cefr' => $resource->level?->name,
                    ]);
                }
            }

            foreach ([$resource->listeningResource, $resource->readingResource] as $interactive) {
                if ($interactive !== null) {
                    $interactive->questions->each(function (Question $question) use ($resource, $recommendation): void {
                        $correct = $question->options->where('is_correct', true)->count();
                        $note = $correct === 1 ? 'One correct option is configured; answer key and explanation still require teacher validation.' : "Expected one correct option; found {$correct}.";
                        $this->addRow('Interactive question', $question, $correct === 1 ? $recommendation : 'revise', 'n/a', $note, [
                            'parent_slug' => $resource->slug,
                            'question_type' => $question->type,
                            'answer_key' => $correct === 1 ? 'one correct option' : 'invalid/multiple correct options',
                        ]);
                    });
                }
            }
        });
    }

    private function auditTests(): void
    {
        Test::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->with(['level', 'skill', 'sections.questions.options', 'translations'])->orderBy('id')->each(function (Test $test): void {
            $questions = $test->sections->flatMap(fn ($section) => $section->questions);
            $missingExplanations = $questions->filter(fn (Question $question): bool => blank($question->explanation))->count();
            $invalidAnswerKeys = $questions->filter(fn (Question $question): bool => in_array($question->type, ['single_choice', 'true_false'], true)
                && $question->options->where('is_correct', true)->count() !== 1)->count();
            $difficulties = $questions->pluck('difficulty')->unique()->implode('; ');
            $notes = [];
            if ($missingExplanations > 0) {
                $notes[] = "{$missingExplanations} of {$questions->count()} questions have no explanation.";
            }
            if ($invalidAnswerKeys > 0) {
                $notes[] = "{$invalidAnswerKeys} questions have an invalid single-answer key.";
            }
            $notes[] = 'Verify question difficulty, curriculum coverage and CEFR alignment against the assigned test level.';
            $this->addRow('Test', $test, 'revise', 'n/a', implode(' ', $notes), [
                'cefr' => $test->level?->name,
                'skills' => $test->skill?->name,
                'question_count' => $questions->count(),
                'difficulty_distribution' => $difficulties,
                'answer_key' => $invalidAnswerKeys === 0 ? 'structurally valid; teacher validation pending' : 'requires correction',
                'translation' => $test->translationStatus('title', 'ro'),
            ]);

            foreach ($test->sections as $section) {
                $this->addRow('Test section', $section, 'revise', 'n/a', 'Review section objective, skill/topic coverage, question progression and Romanian instructions.', [
                    'parent_slug' => $test->slug,
                    'cefr' => $test->level?->name,
                    'question_count' => $section->questions->count(),
                ]);
                foreach ($section->questions as $question) {
                    $correct = $question->options->where('is_correct', true)->count();
                    $this->addRow('Test question', $question, 'revise', 'n/a', trim(($question->explanation ? 'Explanation supplied; ' : 'Explanation missing; ').'teacher validation of wording, distractors, key and CEFR fit required.'), [
                        'parent_slug' => $test->slug,
                        'cefr' => $test->level?->name,
                        'question_type' => $question->type,
                        'difficulty' => $question->difficulty,
                        'answer_key' => $correct === 1 ? 'one correct option' : "{$correct} correct options",
                    ]);
                }
            }
        });
    }

    private function auditLessons(): void
    {
        LearningLesson::query()->where('editorial_status', '!=', 'grandfathered')->where('status', 'published')->with(['unit.path', 'level', 'skills', 'topics', 'activities'])->orderBy('id')->each(function (LearningLesson $lesson): void {
            $notes = $lesson->publicationIssues();
            $notes[] = 'Review lesson objectives, CEFR fit, activity sequencing and each referenced resource/test.';
            $this->addRow('Learning lesson', $lesson, 'revise', 'n/a', implode(' ', $notes), [
                'parent_slug' => $lesson->unit?->path?->slug,
                'cefr' => $lesson->level?->name,
                'skills' => $lesson->skills->pluck('name')->implode('; '),
                'topics' => $lesson->topics->pluck('name')->implode('; '),
                'activity_count' => $lesson->activities->count(),
                'translation' => $lesson->translationStatus('title', 'ro'),
            ]);
        });
    }

    private function auditTranslations(): void
    {
        ContentTranslation::query()->with('translatable')->where('editorial_status', '!=', 'grandfathered')->where('locale', 'ro')->orderBy('id')->each(function (ContentTranslation $translation): void {
            $source = $translation->translatable?->getAttribute($translation->field);
            $sourceValue = is_array($source)
                ? json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
                : (string) $source;
            $stale = $source === null || ! hash_equals((string) $translation->source_hash, $sourceValue === '' ? '' : hash('sha256', $sourceValue));
            $status = $stale ? 'stale' : ($translation->editorial_status === 'approved' && $translation->status === 'reviewed' ? 'approved' : 'pending');
            $this->addRow('Translation', $translation, $status === 'approved' ? 'approved' : 'revise', 'n/a', $stale ? 'Source text changed or no longer exists; translation must be refreshed.' : 'Check pedagogical meaning, grammar terminology, diacritics and fallback before approval.', [
                'parent_slug' => $translation->translatable?->getAttribute('slug'),
                'locale' => $translation->locale,
                'translation_field' => $translation->field,
                'translation_status' => $status,
            ]);
        });
    }

    /** @param array<string, string|int|null> $extra */
    private function addRow(string $type, Model $model, string $recommendation, string $media, string $notes, array $extra = []): void
    {
        $reviewer = $model->getAttribute('editorial_reviewed_by');
        $this->rows[] = array_merge([
            'record_type' => $type,
            'id' => (string) $model->getKey(),
            'slug' => (string) ($model->getAttribute('slug') ?? ''),
            'title' => (string) ($model->getAttribute('title') ?? $model->getAttribute('name') ?? $model->getAttribute('question') ?? $model->getAttribute('field') ?? ''),
            'parent_slug' => null,
            'cefr' => null,
            'skills' => null,
            'topics' => null,
            'published_status' => (string) ($model->getAttribute('status') ?? ''),
            'editorial_status' => (string) ($model->getAttribute('editorial_status') ?? 'not_applicable'),
            'recommended_decision' => $recommendation,
            'reviewer_id' => $reviewer === null ? null : (string) $reviewer,
            'reviewed_at' => (string) ($model->getAttribute('editorial_reviewed_at') ?? ''),
            'media' => $media,
            'seo' => null,
            'translation' => null,
            'locale' => null,
            'translation_field' => null,
            'translation_status' => null,
            'question_type' => null,
            'difficulty' => null,
            'answer_key' => null,
            'question_count' => null,
            'activity_count' => null,
            'difficulty_distribution' => null,
            'notes' => $notes,
        ], $extra);
    }

    /** @return array<string, string|null> */
    private function headers(): array
    {
        return [
            'record_type' => null, 'id' => null, 'slug' => null, 'title' => null, 'parent_slug' => null,
            'cefr' => null, 'skills' => null, 'topics' => null, 'published_status' => null,
            'editorial_status' => null, 'recommended_decision' => null, 'reviewer_id' => null,
            'reviewed_at' => null, 'media' => null, 'seo' => null, 'translation' => null,
            'locale' => null, 'translation_field' => null, 'translation_status' => null,
            'question_type' => null, 'difficulty' => null, 'answer_key' => null,
            'question_count' => null, 'activity_count' => null, 'difficulty_distribution' => null, 'notes' => null,
        ];
    }
}
