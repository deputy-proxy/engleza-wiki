<?php

namespace App\Console\Commands;

use App\Services\Assessment\AssessmentBankAuditService;
use Illuminate\Console\Command;
use JsonException;

class AuditAssessmentBank extends Command
{
    protected $signature = 'assessment:bank-audit
        {test? : Optional test slug to inspect}
        {--include-grandfathered : Include existing demo assessments for diagnostic inventory only}
        {--format=table : Output format: table or json}';

    protected $description = 'Check assessment question banks for blueprint coverage, duplicate prompts, invalid definitions and answer imbalance.';

    /** @throws JsonException */
    public function handle(AssessmentBankAuditService $audit): int
    {
        $format = strtolower((string) $this->option('format'));
        if (! in_array($format, ['table', 'json'], true)) {
            $this->error('The --format option must be table or json.');

            return self::INVALID;
        }

        $includeGrandfathered = (bool) $this->option('include-grandfathered');

        /** @var array{
         *     summary: array{tests: int, questions: int, duplicate_prompts: int, errors: int, warnings: int},
         *     tests: array<int, array{slug: string, level: ?string, question_count: int, duration_minutes: ?int, sections: array<string, int>, issue_count: int}>,
         *     issues: array<int, array{code: string, severity: string, test_slug: string, question_id: ?int, message: string}>,
         *     duplicates: array<int, array<string, mixed>>, scope: string
         * } $result */
        $result = $audit->audit(
            is_string($this->argument('test')) ? $this->argument('test') : null,
            $includeGrandfathered,
        );

        if ($format === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } else {
            if ($includeGrandfathered) {
                $this->warn('Diagnostic inventory includes grandfathered demo content. It does not approve, edit or block those records.');
            }

            $this->table(
                ['Test', 'Level', 'Questions', 'Duration', 'Sections', 'Issues'],
                array_map(fn (array $test): array => [
                    $test['slug'],
                    $test['level'] ?? '—',
                    $test['question_count'],
                    $test['duration_minutes'] === null ? '—' : $test['duration_minutes'].' min',
                    collect($test['sections'])->map(fn (int $count, string $section): string => "{$section}: {$count}")->implode(', '),
                    $test['issue_count'],
                ], $result['tests']),
            );

            $this->table(
                ['Code', 'Severity', 'Test', 'Question', 'Finding'],
                array_map(fn (array $issue): array => [
                    $issue['code'], $issue['severity'], $issue['test_slug'], $issue['question_id'] ?? '—', $issue['message'],
                ], $result['issues']),
            );

            $this->table(['Metric', 'Count'], collect($result['summary'])->map(fn (int $count, string $metric): array => [$metric, $count])->values()->all());
        }

        return $result['summary']['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
