<?php

namespace App\Services\Test;

use App\Models\AssessmentResult;
use App\Models\Question;
use App\Models\Resource;
use App\Models\TestAttempt;
use App\Services\Assessment\QuestionScoringService;
use Illuminate\Support\Facades\DB;

class TestScoringService
{
    public function __construct(
        private readonly QuestionScoringService $questionScoring,
        private readonly TestQuestionOrderService $questionOrder,
    ) {}

    public function score(TestAttempt $attempt): AssessmentResult
    {
        return DB::transaction(function () use ($attempt): AssessmentResult {
            $locked = TestAttempt::query()->with('test.level')->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $existing = AssessmentResult::where('test_attempt_id', $locked->id)->first();
            if (in_array($locked->status, ['completed', 'expired'], true) && $existing) {
                return $existing;
            }
            if (in_array($locked->status, ['completed', 'expired'], true) && $locked->question_order === null) {
                abort(409, __('This historical attempt cannot be rescored because its original question set was not recorded.'));
            }
            abort_unless(in_array($locked->status, ['started', 'completed', 'expired'], true), 410);
            $order = $this->questionOrder->forAttempt($locked);
            $questionsById = Question::query()
                ->whereIn('id', $order)
                ->whereIn('test_section_id', $locked->test->sections()->select('id'))
                ->with(['section', 'topics'])
                ->get()
                ->keyBy('id');
            $questions = collect($order)->map(fn (int $questionId) => $questionsById->get($questionId))->filter()->values();
            $answers = $locked->answers()->with('option')->get();
            $scores = $this->questionScoring->score($questions, $answers);

            foreach ($scores['question_scores'] as $questionId => $score) {
                $answer = $answers->firstWhere('question_id', $questionId);
                if ($answer) {
                    $answer->forceFill(['is_correct' => $score['is_correct'], 'points_awarded' => $score['earned_points']])->save();
                }
            }

            $pct = $scores['percentage'];
            $level = $this->estimateLevel($pct, $locked->test->level?->slug);
            $skill = fn (string $name): ?float => isset($scores['by_section'][$name]) && $scores['by_section'][$name]['total'] > 0
                ? round(($scores['by_section'][$name]['earned'] / $scores['by_section'][$name]['total']) * 100, 1) : null;
            $weak = collect($scores['by_topic'])->filter(fn (array $topic): bool => $topic['total'] > 0 && ($topic['earned'] / $topic['total']) * 100 < 70);
            $resourceIds = Resource::published()->whereHas('topics', fn ($query) => $query->whereIn('topics.id', $weak->keys()))->limit(6)->pluck('id')->all();
            $recommendation = json_encode(['weak_topics' => $weak->pluck('name')->values()->all(), 'resource_ids' => $resourceIds], JSON_THROW_ON_ERROR);

            $locked->forceFill([
                'score' => $scores['earned_points'], 'total_points' => $scores['total_points'], 'percentage' => $pct,
                'level_result' => $level, 'passed' => $locked->test->passing_score === null ? null : ($scores['total_points'] > 0 && $pct >= $locked->test->passing_score),
                'status' => $locked->status === 'expired' ? 'expired' : 'completed', 'completed_at' => now(),
                'time_spent_seconds' => $locked->started_at ? max(0, (int) $locked->started_at->diffInSeconds(now(), false)) : null,
            ])->save();

            return AssessmentResult::updateOrCreate(['test_attempt_id' => $locked->id], [
                'overall_score' => $scores['earned_points'], 'overall_percentage' => $pct, 'total_points' => $scores['total_points'],
                'breakdown' => $scores['breakdown'], 'level' => $level,
                'grammar_score' => $skill('Grammar'), 'vocabulary_score' => $skill('Vocabulary'),
                'reading_score' => $skill('Reading'), 'writing_score' => $skill('Writing'),
                'listening_score' => $skill('Listening'), 'speaking_score' => $skill('Speaking'), 'recommendation' => $recommendation,
            ]);
        });
    }

    private function estimateLevel(float $percentage, ?string $slug): string
    {
        if ($slug === null) {
            if ($percentage >= 90) {
                return 'C1';
            }
            if ($percentage >= 75) {
                return 'B2';
            }
            if ($percentage >= 55) {
                return 'B1';
            }
            if ($percentage >= 35) {
                return 'A2';
            }

            return 'A1';
        }

        $levels = ['pre-a1', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'];
        $index = array_search($slug, $levels, true);
        $index = $index === false ? 2 : $index;
        if ($percentage >= 90 && $index < count($levels) - 1) {
            return strtoupper($levels[$index + 1]);
        }
        if ($percentage >= 60) {
            return strtoupper($levels[$index]);
        }
        if ($percentage >= 30 && $index > 0) {
            return strtoupper($levels[$index - 1]);
        }

        return strtoupper($levels[max(0, $index - 1)]);
    }
}
