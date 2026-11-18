<?php

namespace App\Services\Test;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Assessment\QuestionResponseService;
use Illuminate\Support\Facades\DB;

class TestAttemptService
{
    public function __construct(
        private readonly TestQuestionOrderService $questionOrder,
        private readonly TestScoringService $scoring,
        private readonly QuestionResponseService $responses,
    ) {}

    public function authorizeStart(Test $test, int|string|null $userId): void
    {
        abort_unless($test->status === 'published' && $test->is_public, 404);
        if ($test->access_type === 'registered') {
            abort_unless($userId !== null, 403);
        } else {
            abort_unless($test->access_type === 'free', 403);
        }

        $questions = $test->sections()->with(['translations', 'questions.translations', 'questions.options', 'questions.topics.translations'])->get()->flatMap(fn ($section) => $section->questions);
        foreach ($questions as $question) {
            abort_if($this->responses->definitionIssues($question) !== [], 422, __('This test contains an invalid question configuration.'));
        }
    }

    /** @param array<string, mixed> $attributes */
    public function start(Test $test, array $attributes): TestAttempt
    {
        $userId = $attributes['user_id'] ?? null;
        $this->authorizeStart($test, is_int($userId) || is_string($userId) ? $userId : null);
        if ($userId === null) {
            abort_unless(is_string($attributes['session_id'] ?? null) && $attributes['session_id'] !== '', 401);
        }
        $attributes['question_order'] = $this->questionOrder->forTest($test);
        $attributes['user_agent'] = is_string($attributes['user_agent'] ?? null) ? substr($attributes['user_agent'], 0, 255) : null;

        return $test->attempts()->create($attributes);
    }

    public function authorize(TestAttempt $attempt, int|string|null $userId, ?string $sessionId): void
    {
        if ($attempt->user_id !== null) {
            abort_unless((string) $userId === (string) $attempt->user_id, 403);

            return;
        }
        abort_unless($sessionId !== null && hash_equals((string) $attempt->session_id, $sessionId), 403);
    }

    public function recordAnswer(TestAttempt $attempt, int $questionId, int $optionId, int|string|null $userId, ?string $sessionId): bool
    {
        return $this->recordResponse($attempt, $questionId, $optionId, $userId, $sessionId);
    }

    public function recordResponse(TestAttempt $attempt, int $questionId, mixed $response, int|string|null $userId, ?string $sessionId): bool
    {
        return DB::transaction(function () use ($attempt, $questionId, $response, $userId, $sessionId): bool {
            $locked = TestAttempt::query()->with('test')->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $userId, $sessionId);
            if ($locked->status !== 'started' || $this->expired($locked)) {
                if ($locked->status === 'started' && $this->expired($locked)) {
                    $this->questionOrder->forAttempt($locked);
                    $locked->forceFill(['status' => 'expired'])->save();
                }

                return false;
            }
            $order = $this->questionOrder->forAttempt($locked);
            if (! in_array($questionId, $order, true)
                || ! Question::query()->whereKey($questionId)->whereIn('test_section_id', $locked->test->sections()->select('id'))->exists()) {
                return false;
            }
            $question = Question::query()->with(['translations', 'options', 'topics.translations', 'section.translations'])->find($questionId);
            if (! $question) {
                return false;
            }
            $evaluation = $this->responses->evaluate($question, $response);
            if (! $evaluation['valid']) {
                return false;
            }
            $normalized = $evaluation['normalized'];
            $optionId = is_int($normalized) ? $normalized : null;
            $answerText = is_string($normalized) ? $normalized : null;
            $locked->answers()->updateOrCreate(
                ['question_id' => $questionId],
                [
                    'question_option_id' => $optionId,
                    'answer_text' => $answerText,
                    'answer_payload' => $normalized,
                    'answered_at' => now(),
                ],
            );

            return true;
        });
    }

    public function complete(TestAttempt $attempt, int|string|null $userId, ?string $sessionId): void
    {
        $this->authorize($attempt, $userId, $sessionId);
        if ($attempt->status === 'completed') {
            return;
        }
        if ($attempt->status === 'started') {
            $this->questionOrder->forAttempt($attempt);
        }
        if ($this->expired($attempt)) {
            $attempt->forceFill(['status' => 'expired'])->save();
        }
        $this->scoring->score($attempt->fresh());
    }

    /** @return array<int, array<string, mixed>> */
    public function questionPayload(TestAttempt $attempt, bool $revealAnswers = false): array
    {
        $order = $this->questionOrder->forAttempt($attempt);
        $revealAnswers = $revealAnswers && $attempt->status === 'completed' && (bool) $attempt->test->show_correct_answers;
        $questionsById = Question::query()
            ->whereIn('id', $order)
            ->whereIn('test_section_id', $attempt->test->sections()->select('id'))
            ->with(['translations', 'section.translations', 'options', 'topics.translations'])
            ->get()
            ->keyBy('id');

        return collect($order)->map(fn (int $questionId) => $questionsById->get($questionId))->filter()
            ->map(fn (Question $question): array => [
                'id' => $question->id,
                'text' => $question->question,
                'type' => $question->type,
                'configuration' => $this->responses->learnerConfiguration($question),
                'section' => $question->section?->translated('title'),
                ...($revealAnswers ? [
                    'explanation' => $question->translated('explanation'),
                    'answer_key' => $this->responses->answerKey($question),
                ] : []),
                'options' => in_array($question->type, ['single_choice', 'multiple_choice', 'true_false'], true)
                    ? $question->options->map(fn (QuestionOption $option): array => [
                        'id' => $option->id,
                        'label' => $option->label,
                        'value' => $option->value,
                        ...($revealAnswers ? ['is_correct' => $option->is_correct] : []),
                    ])->all()
                    : [],
            ])->values()->all();
    }

    private function expired(TestAttempt $attempt): bool
    {
        return $attempt->test->duration_minutes !== null
            && $attempt->started_at !== null
            && now()->greaterThanOrEqualTo($attempt->started_at->copy()->addMinutes($attempt->test->duration_minutes));
    }
}
