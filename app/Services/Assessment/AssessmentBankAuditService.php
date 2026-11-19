<?php

namespace App\Services\Assessment;

use App\Models\Question;
use App\Models\Skill;
use App\Models\Test;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssessmentBankAuditService
{
    public function __construct(private readonly QuestionResponseService $responses) {}

    /**
     * @return array{
     *     summary: array{tests: int, questions: int, duplicate_prompts: int, errors: int, warnings: int},
     *     tests: array<int, array{
     *         id: int, slug: string, title: string, editorial_status: string, level: ?string,
     *         duration_minutes: ?int, question_count: int, sections: array<string, int>,
     *         question_types: array<string, int>, difficulty: array<string, int>, topics: array<int, string>,
     *         correct_option_counts: array<string, int>, blueprint: ?string, issue_count: int
     *     }>,
     *     issues: array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>,
     *     duplicates: array<int, array{prompt: string, test_slugs: array<int, string>, occurrences: int}>,
     *     scope: string
     * }
     */
    public function audit(?string $testSlug = null, bool $includeGrandfathered = false): array
    {
        $query = Test::query()
            ->with(['level', 'skill', 'sections.questions.options', 'sections.questions.topics'])
            ->orderBy('slug');

        if (! $includeGrandfathered) {
            $query->where('editorial_status', '!=', 'grandfathered');
        }

        if ($testSlug !== null) {
            $query->where('slug', $testSlug);
        }

        $tests = [];
        /** @var array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}> $issues */
        $issues = [];
        $knownSkills = Skill::query()->pluck('name')->map(fn (string $name): string => Str::lower($name))->all();
        $promptUsage = [];
        $questionTotal = 0;

        foreach ($query->get() as $test) {
            $questions = $test->sections->flatMap(fn ($section) => $section->questions);
            $questionTotal += $questions->count();
            $sectionCounts = [];
            $difficultyCounts = ['easy' => 0, 'medium' => 0, 'hard' => 0];
            $questionTypeCounts = [];
            $topicNames = [];
            $correctOptionCounts = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0];
            /** @var array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}> $testIssues */
            $testIssues = [];
            $items = [];
            $seenInTest = [];

            foreach ($test->sections as $section) {
                $sectionCounts[$section->title] = ($sectionCounts[$section->title] ?? 0) + $section->questions->count();
                if ($this->blueprintFor($test->slug) !== null && ! in_array(Str::lower($section->title), $knownSkills, true)) {
                    $this->addIssue($issues, $testIssues, 'missing_skill_tag', 'error', $test->slug, null, "Section '{$section->title}' is not mapped to a known skill.");
                }

                foreach ($section->questions as $question) {
                    $questionTypeCounts[$question->type] = ($questionTypeCounts[$question->type] ?? 0) + 1;
                    $difficulty = strtolower((string) $question->difficulty);
                    if (array_key_exists($difficulty, $difficultyCounts)) {
                        $difficultyCounts[$difficulty]++;
                    } else {
                        $this->addIssue($issues, $testIssues, 'invalid_difficulty', 'error', $test->slug, $question->id, "Unsupported difficulty '{$difficulty}'.");
                    }

                    $normalizedPrompt = $this->normalizePrompt((string) $question->question);
                    if (blank($question->question)) {
                        $this->addIssue($issues, $testIssues, 'missing_prompt', 'error', $test->slug, $question->id, 'The question prompt is empty.');
                    }

                    if ($normalizedPrompt !== '') {
                        $promptUsage[$normalizedPrompt][] = ['test_slug' => $test->slug, 'question_id' => $question->id, 'prompt' => $question->question];
                        if (isset($seenInTest[$normalizedPrompt])) {
                            $this->addIssue($issues, $testIssues, 'duplicate_prompt_within_form', 'error', $test->slug, $question->id, 'The same prompt appears more than once in this test.');
                        }
                        $seenInTest[$normalizedPrompt] = true;
                    }

                    if (blank($question->explanation)) {
                        $this->addIssue($issues, $testIssues, 'missing_explanation', 'error', $test->slug, $question->id, 'The question has no answer explanation.');
                    }

                    if ($question->topics->isEmpty()) {
                        $this->addIssue($issues, $testIssues, 'missing_topic', 'error', $test->slug, $question->id, 'The question has no topic tag.');
                    }

                    $metadata = is_array($question->metadata) ? $question->metadata : [];
                    $cefrRationale = $metadata['cefr_rationale'] ?? null;
                    if (blank($cefrRationale)) {
                        $this->addIssue($issues, $testIssues, 'missing_cefr_rationale', 'error', $test->slug, $question->id, 'The question has no CEFR-level rationale in its metadata.');
                    }

                    foreach ($question->topics as $topic) {
                        $topicNames[$topic->name] = true;
                    }

                    foreach ($this->responses->definitionIssues($question) as $definitionIssue) {
                        $this->addIssue($issues, $testIssues, 'invalid_question_definition', 'error', $test->slug, $question->id, $definitionIssue);
                    }

                    $correctOptionLabels = $question->options
                        ->where('is_correct', true)
                        ->pluck('label')
                        ->filter(fn ($label): bool => filled($label))
                        ->map(fn ($label): string => Str::upper((string) $label))
                        ->values()
                        ->all();

                    if ($question->type === 'single_choice') {
                        $correctLabel = $correctOptionLabels[0] ?? null;
                        if ($correctLabel !== null && isset($correctOptionCounts[$correctLabel])) {
                            $correctOptionCounts[$correctLabel]++;
                        }
                    }

                    $items[] = [
                        'id' => $question->id,
                        'prompt' => $question->question,
                        'level' => $test->level->name,
                        'skill' => $section->title,
                        'topic_tags' => $question->topics->pluck('name')->all(),
                        'type' => $question->type,
                        'difficulty' => $difficulty,
                        'option_count' => $question->options->count(),
                        'correct_option_labels' => $correctOptionLabels,
                        'explanation_present' => filled($question->explanation),
                        'cefr_rationale_present' => filled($cefrRationale),
                        'cefr_rationale' => is_string($cefrRationale) ? $cefrRationale : null,
                        'form_review_status' => $test->editorial_status,
                        'form_reviewed_by' => $this->reviewerId($test),
                        'form_reviewed_at' => $this->reviewedAt($test),
                        'reused_across_forms' => false,
                        'used_in_forms' => [$test->slug],
                    ];
                }
            }

            $blueprint = $this->blueprintFor($test->slug);
            if ($blueprint !== null) {
                $this->auditBlueprint($test, $questions, $sectionCounts, $questionTypeCounts, $difficultyCounts, array_keys($topicNames), $correctOptionCounts, $blueprint, $issues, $testIssues);
            }

            $tests[] = [
                'id' => $test->id,
                'slug' => $test->slug,
                'title' => $test->title,
                'editorial_status' => $test->editorial_status,
                'level' => $test->level?->name,
                'duration_minutes' => $test->duration_minutes,
                'question_count' => $questions->count(),
                'sections' => $sectionCounts,
                'question_types' => $questionTypeCounts,
                'difficulty' => $difficultyCounts,
                'topics' => array_keys($topicNames),
                'correct_option_counts' => $correctOptionCounts,
                'blueprint' => $blueprint['label'] ?? null,
                'issue_count' => count($testIssues),
                'reviewed_by' => $this->reviewerId($test),
                'reviewed_at' => $this->reviewedAt($test),
                'review_notes' => $this->reviewNotes($test),
                'items' => $items,
            ];
        }

        $duplicates = [];
        foreach ($promptUsage as $prompt => $occurrences) {
            $testSlugs = array_values(array_unique(array_column($occurrences, 'test_slug')));
            if (count($testSlugs) < 2) {
                continue;
            }

            $duplicates[] = [
                'prompt' => $occurrences[0]['prompt'],
                'test_slugs' => $testSlugs,
                'occurrences' => count($occurrences),
            ];

            foreach ($occurrences as $occurrence) {
                /** @var array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}> $unusedTestIssues */
                $unusedTestIssues = [];
                $this->addIssue($issues, $unusedTestIssues, 'prompt_reused_across_forms', 'error', $occurrence['test_slug'], $occurrence['question_id'], 'This prompt is reused across assessment forms: '.implode(', ', $testSlugs).'.');
            }
        }

        foreach ($tests as &$testResult) {
            foreach ($testResult['items'] as &$item) {
                $normalizedPrompt = $this->normalizePrompt($item['prompt']);
                $forms = array_values(array_unique(array_column($promptUsage[$normalizedPrompt] ?? [], 'test_slug')));
                $item['used_in_forms'] = $forms;
                $item['reused_across_forms'] = count($forms) > 1;
            }
            unset($item);

            $testResult['issue_count'] = count(array_filter(
                $issues,
                fn (array $issue): bool => $issue['test_slug'] === $testResult['slug'],
            ));
        }
        unset($testResult);

        $summary = [
            'tests' => count($tests),
            'questions' => $questionTotal,
            'duplicate_prompts' => count($duplicates),
            'errors' => count(array_filter($issues, fn (array $issue): bool => $issue['severity'] === 'error')),
            'warnings' => count(array_filter($issues, fn (array $issue): bool => $issue['severity'] === 'warning')),
        ];

        return [
            'summary' => $summary,
            'tests' => $tests,
            'issues' => $issues,
            'duplicates' => $duplicates,
            'scope' => $includeGrandfathered ? 'all records, including grandfathered demo content; diagnostic only' : 'prospective records only; grandfathered demo content excluded',
        ];
    }

    /** @return array<string, mixed>|null */
    private function blueprintFor(string $slug): ?array
    {
        $blueprints = array_merge(
            config('assessment.placement_blueprints', []),
            config('assessment.focused_blueprints', []),
        );

        foreach ($blueprints as $blueprint) {
            if (($blueprint['slug'] ?? null) === $slug) {
                return $blueprint;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Question>  $questions
     * @param  array<string, int>  $sectionCounts
     * @param  array<string, int>  $questionTypeCounts
     * @param  array<string, int>  $difficultyCounts
     * @param  array<int, string>  $topicNames
     * @param  array<string, int>  $correctOptionCounts
     * @param  array<string, mixed>  $blueprint
     * @param  array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>  $issues
     * @param  array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>  $testIssues
     */
    private function auditBlueprint(Test $test, Collection $questions, array $sectionCounts, array $questionTypeCounts, array $difficultyCounts, array $topicNames, array $correctOptionCounts, array $blueprint, array &$issues, array &$testIssues): void
    {
        if ($test->level->slug !== ($blueprint['level_slug'] ?? null)) {
            $actualLevel = $test->level->slug;
            $this->addIssue($issues, $testIssues, 'blueprint_level_mismatch', 'error', $test->slug, null, "Expected level {$blueprint['level_slug']}; found {$actualLevel}.");
        }

        if ($questions->count() !== $blueprint['question_count']) {
            $this->addIssue($issues, $testIssues, 'blueprint_question_count', 'error', $test->slug, null, "Expected {$blueprint['question_count']} questions; found {$questions->count()}.");
        }

        if ((int) $test->duration_minutes !== (int) $blueprint['duration_minutes']) {
            $this->addIssue($issues, $testIssues, 'blueprint_duration', 'error', $test->slug, null, "Expected {$blueprint['duration_minutes']} minutes; found {$test->duration_minutes}.");
        }

        if ($questionTypeCounts !== $blueprint['question_types']) {
            $this->addIssue($issues, $testIssues, 'blueprint_question_types', 'error', $test->slug, null, 'Expected question types '.json_encode($blueprint['question_types']).'; found '.json_encode($questionTypeCounts).'.');
        }

        foreach ($blueprint['sections'] as $section => $expectedCount) {
            $actualCount = $sectionCounts[$section] ?? 0;
            if ($actualCount !== $expectedCount) {
                $this->addIssue($issues, $testIssues, 'blueprint_section_count', 'error', $test->slug, null, "{$section}: expected {$expectedCount} questions; found {$actualCount}.");
            }
        }

        foreach ($sectionCounts as $section => $actualCount) {
            if (! array_key_exists($section, $blueprint['sections'])) {
                $this->addIssue($issues, $testIssues, 'unexpected_section', 'error', $test->slug, null, "Unexpected assessment section '{$section}' contains {$actualCount} questions.");
            }
        }

        foreach ($blueprint['difficulty'] as $difficulty => $expectedCount) {
            $actualCount = $difficultyCounts[$difficulty] ?? 0;
            if ($actualCount !== $expectedCount) {
                $this->addIssue($issues, $testIssues, 'blueprint_difficulty', 'error', $test->slug, null, "Difficulty '{$difficulty}': expected {$expectedCount}; found {$actualCount}.");
            }
        }

        $missingTopics = array_values(array_diff($blueprint['required_topics'], $topicNames));
        if ($missingTopics !== []) {
            $this->addIssue($issues, $testIssues, 'blueprint_topic_coverage', 'error', $test->slug, null, 'Missing required topics: '.implode(', ', $missingTopics).'.');
        }

        foreach ($blueprint['minimum_topics'] as $sectionTitle => $minimum) {
            $section = $test->sections->firstWhere('title', $sectionTitle);
            $sectionTopics = $section?->questions
                ->flatMap(fn ($question) => $question->topics->pluck('name'))
                ->unique() ?? collect();
            if ($sectionTopics->count() < $minimum) {
                $this->addIssue($issues, $testIssues, 'insufficient_section_topic_coverage', 'error', $test->slug, null, "{$sectionTitle}: expected at least {$minimum} distinct topic tags; found {$sectionTopics->count()}.");
            }
        }

        $singleChoiceCount = $questions->where('type', 'single_choice')->count();
        if ($singleChoiceCount >= (int) ($blueprint['minimum_answer_balance_count'] ?? 12)) {
            foreach ($blueprint['correct_option_counts'] as $label => [$minimum, $maximum]) {
                $actualCount = $correctOptionCounts[$label] ?? 0;
                if ($actualCount < $minimum || $actualCount > $maximum) {
                    $this->addIssue($issues, $testIssues, 'answer_position_imbalance', 'warning', $test->slug, null, "Correct answer position {$label} appears {$actualCount} times; expected {$minimum}-{$maximum}.");
                }
            }
        }

        if (($blueprint['multi_skill'] ?? false) && $test->skill !== null) {
            $this->addIssue($issues, $testIssues, 'single_skill_metadata_on_multiskill_form', 'warning', $test->slug, null, 'This form measures Grammar, Vocabulary and Reading but declares only one primary test skill.');
        }
    }

    /**
     * @param  array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>  $issues
     * @param  array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>  $testIssues
     */
    private function addIssue(array &$issues, array &$testIssues, string $code, string $severity, string $testSlug, ?int $questionId, string $message): void
    {
        $issue = [
            'code' => $code,
            'severity' => $severity,
            'test_slug' => $testSlug,
            'question_id' => $questionId,
            'message' => $message,
        ];
        $issues[] = $issue;
        $testIssues[] = $issue;
    }

    private function reviewerId(Test $test): ?int
    {
        $reviewerId = $test->getAttribute('editorial_reviewed_by');

        return is_numeric($reviewerId) ? (int) $reviewerId : null;
    }

    private function reviewedAt(Test $test): ?string
    {
        $reviewedAt = $test->getAttribute('editorial_reviewed_at');

        if ($reviewedAt instanceof \DateTimeInterface) {
            return $reviewedAt->format(DATE_ATOM);
        }

        return is_string($reviewedAt) && $reviewedAt !== '' ? $reviewedAt : null;
    }

    private function reviewNotes(Test $test): ?string
    {
        $notes = $test->getAttribute('editorial_notes');

        return is_string($notes) ? $notes : null;
    }

    private function normalizePrompt(string $prompt): string
    {
        return Str::of($prompt)->lower()->replaceMatches('/\s+/u', ' ')->trim()->toString();
    }
}
