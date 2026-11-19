<?php

namespace App\Console\Commands;

use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningUnit;
use App\Models\Level;
use Illuminate\Console\Command;

class AuditLearningCurriculum extends Command
{
    protected $signature = 'learning:curriculum-audit';

    protected $description = 'Verify that A1–B2 learning paths meet the documented curriculum publication standard.';

    /** @var array<int, array{level: string, path: string, issue: string}> */
    private array $issues = [];

    public function handle(): int
    {
        foreach (['a1' => 'A1', 'a2' => 'A2', 'b1' => 'B1', 'b2' => 'B2'] as $slug => $label) {
            $level = Level::query()->where('slug', $slug)->first();

            if ($level === null) {
                $this->addIssue($label, '—', 'CEFR level record is missing.');

                continue;
            }

            $paths = LearningPath::query()
                ->where('level_id', $level->id)
                ->where('status', 'published')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->with(['units.lessons.activities.resource', 'units.lessons.activities.test'])
                ->get();

            if ($paths->isEmpty()) {
                $this->addIssue($label, '—', 'No published learning path exists for this level.');

                continue;
            }

            foreach ($paths as $path) {
                $pathLabel = $path->title.' ('.$path->slug.')';
                $units = $path->units;

                if ($units->count() < 5) {
                    $this->addIssue($label, $pathLabel, 'Expected at least five ordered curriculum units; found '.$units->count().'.');
                }

                foreach ($units as $unit) {
                    $this->auditUnit($label, $pathLabel, $unit);
                }
            }
        }

        if ($this->issues !== []) {
            $this->table(
                ['Level', 'Path', 'Publication issue'],
                array_map(fn (array $issue): array => [$issue['level'], $issue['path'], $issue['issue']], $this->issues),
            );
            $this->error(count($this->issues).' curriculum publication issue(s) found.');

            return self::FAILURE;
        }

        $this->info('A1, A2, B1 and B2 each have published paths meeting the curriculum structure and editorial-readiness checks.');

        return self::SUCCESS;
    }

    private function auditUnit(string $level, string $path, LearningUnit $unit): void
    {
        $unitLabel = $path.' / '.$unit->title;

        if (! $unit->is_published) {
            $this->addIssue($level, $unitLabel, 'Unit is not published.');
        }

        if ($unit->lessons->count() < 4) {
            $this->addIssue($level, $unitLabel, 'Expected at least four ordered lessons; found '.$unit->lessons->count().'.');
        }

        $hasApprovedCheckpoint = false;

        foreach ($unit->lessons as $lesson) {
            $this->auditLesson($level, $unitLabel, $lesson);

            foreach ($lesson->activities as $activity) {
                if ($activity->type === 'test'
                    && $activity->test !== null
                    && $activity->test->status === 'published'
                    && $activity->test->editorial_status === 'approved'
                    && $activity->test->is_public
                    && in_array($activity->test->access_type, ['free', 'registered'], true)) {
                    $hasApprovedCheckpoint = true;
                }
            }
        }

        if (! $hasApprovedCheckpoint) {
            $this->addIssue($level, $unitLabel, 'Unit has no published, editorially approved and learner-accessible test checkpoint.');
        }
    }

    private function auditLesson(string $level, string $unit, LearningLesson $lesson): void
    {
        $lessonLabel = $unit.' / '.$lesson->title;

        if ($lesson->status !== 'published' || $lesson->published_at === null || $lesson->published_at->gt(now())) {
            $this->addIssue($level, $lessonLabel, 'Lesson is not currently published.');
        }

        if ($lesson->editorial_status !== 'approved') {
            $this->addIssue($level, $lessonLabel, 'Lesson must be explicitly editorially approved; grandfathered demo content does not qualify.');
        }

        if ($lesson->objectives === null || $lesson->objectives === []) {
            $this->addIssue($level, $lessonLabel, 'Lesson has no measurable learning objectives.');
        }

        if ((int) $lesson->level_id !== (int) $lesson->unit->path->level_id) {
            $this->addIssue($level, $lessonLabel, 'Lesson CEFR level does not match its learning path.');
        }

        if ($lesson->activities->count() < 2) {
            $this->addIssue($level, $lessonLabel, 'Lesson needs at least two ordered activities for instructional input and practice.');
        }

        foreach ($lesson->activities as $activity) {
            if ($activity->type === 'resource' && ($activity->resource === null || $activity->resource->editorial_status !== 'approved')) {
                $this->addIssue($level, $lessonLabel, 'Every linked resource must be explicitly editorially approved.');
            }

            if ($activity->type === 'test' && ($activity->test === null || $activity->test->editorial_status !== 'approved')) {
                $this->addIssue($level, $lessonLabel, 'Every linked assessment must be explicitly editorially approved.');
            }
        }

        foreach ($lesson->publicationIssues() as $issue) {
            $this->addIssue($level, $lessonLabel, $issue);
        }
    }

    private function addIssue(string $level, string $path, string $issue): void
    {
        $this->issues[] = ['level' => $level, 'path' => $path, 'issue' => $issue];
    }
}
