<?php

namespace App\Services\Learning;

use App\Models\LearningAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Resource;
use App\Services\Assessment\QuestionResponseService;
use App\Services\Assessment\QuestionScoringService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LearningAttemptService
{
    public function __construct(
        private readonly QuestionScoringService $questionScoring,
        private readonly QuestionResponseService $responses,
    ) {}

    public function start(Resource $resource, int|string|null $userId, ?string $sessionId, ?string $ip, ?string $userAgent): LearningAttempt
    {
        abort_unless($resource->status === 'published' && in_array($resource->type, ['listening', 'reading'], true), 404);
        if ($userId === null) {
            abort_unless(is_string($sessionId) && $sessionId !== '', 401);
        }
        $questions = $this->questions($resource)->load('options');
        foreach ($questions as $question) {
            abort_if($this->responses->definitionIssues($question) !== [], 422, 'This exercise contains an invalid question configuration.');
        }

        return LearningAttempt::create([
            'resource_id' => $resource->id, 'user_id' => $userId, 'session_id' => $userId === null ? $sessionId : null,
            'question_order' => $questions->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            'started_at' => now(), 'status' => 'started', 'ip_address' => $ip,
            'user_agent' => $userAgent === null ? null : substr($userAgent, 0, 255),
        ]);
    }

    public function authorize(LearningAttempt $attempt, int|string|null $userId, ?string $sessionId): void
    {
        if ($attempt->user_id !== null) {
            abort_unless((string) $userId === (string) $attempt->user_id, 403);

            return;
        }
        abort_unless($sessionId !== null && hash_equals((string) $attempt->session_id, $sessionId), 403);
    }

    public function recordAnswer(LearningAttempt $attempt, int $questionId, int $optionId, int|string|null $userId, ?string $sessionId): bool
    {
        return $this->recordResponse($attempt, $questionId, $optionId, $userId, $sessionId);
    }

    public function recordResponse(LearningAttempt $attempt, int $questionId, mixed $response, int|string|null $userId, ?string $sessionId): bool
    {
        return DB::transaction(function () use ($attempt, $questionId, $response, $userId, $sessionId): bool {
            $locked = LearningAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $userId, $sessionId);
            if ($locked->status !== 'started') {
                return false;
            }
            $question = $this->questionsForAttempt($locked)->firstWhere('id', $questionId);
            if (! $question) {
                return false;
            }
            $question->load('options');
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

    public function complete(LearningAttempt $attempt, int|string|null $userId, ?string $sessionId): LearningAttempt
    {
        return DB::transaction(function () use ($attempt, $userId, $sessionId): LearningAttempt {
            $locked = LearningAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();
            $this->authorize($locked, $userId, $sessionId);
            if ($locked->status === 'completed') {
                return $locked->load(['answers.question', 'answers.option']);
            }
            abort_unless($locked->status === 'started', 410);
            $questions = $this->questionsForAttempt($locked)->load(['topics', 'section', 'options']);
            $answers = $locked->answers()->with('option')->get();
            $scores = $this->questionScoring->score($questions, $answers);
            foreach ($scores['question_scores'] as $questionId => $score) {
                $answer = $answers->firstWhere('question_id', $questionId);
                if ($answer) {
                    $answer->forceFill(['is_correct' => $score['is_correct'], 'points_awarded' => $score['earned_points']])->save();
                }
            }
            $locked->forceFill([
                'score' => $scores['earned_points'], 'total_points' => $scores['total_points'],
                'percentage' => $scores['percentage'], 'breakdown' => $scores['breakdown'],
                'status' => 'completed', 'completed_at' => now(),
            ])->save();

            return $locked->fresh(['answers.question', 'answers.option']);
        });
    }

    /** @return array<int, array<string, mixed>> */
    public function questionPayload(Resource $resource, bool $revealAnswers = false, ?LearningAttempt $attempt = null): array
    {
        $settings = $resource->type === 'listening' ? $resource->listeningResource : $resource->readingResource;
        $revealAnswers = $revealAnswers && $attempt !== null && $attempt->status === 'completed'
            && ($settings->show_correct_answers ?? true);
        $questions = $attempt === null ? $this->questions($resource) : $this->questionsForAttempt($attempt);

        return $questions->load('options')->map(fn (Question $question): array => [
            'id' => $question->id,
            'text' => $question->question,
            'type' => $question->type,
            'configuration' => $this->responses->learnerConfiguration($question),
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
        ])->all();
    }

    /** @return array<int, mixed> */
    public function answersForAttempt(LearningAttempt $attempt): array
    {
        return $attempt->answers()->get()->mapWithKeys(fn ($answer): array => [
            (int) $answer->question_id => $answer->answer_payload
                ?? ($answer->question_option_id !== null ? (int) $answer->question_option_id : $answer->answer_text),
        ])->all();
    }

    /** @return Collection<int, Question> */
    private function questionsForAttempt(LearningAttempt $attempt): Collection
    {
        if ($attempt->question_order === null) {
            if (in_array($attempt->status, ['completed', 'expired'], true)) {
                return new Collection;
            }
            $attempt->forceFill([
                'question_order' => $this->questions($attempt->resource)->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ])->save();
        }

        $order = $attempt->question_order ?? [];
        $questionsById = Question::query()
            ->whereIn('id', $order)
            ->where(function ($query) use ($attempt): void {
                $query->whereIn('listening_resource_id', $attempt->resource->listeningResource()->select('id'))
                    ->orWhereIn('reading_resource_id', $attempt->resource->readingResource()->select('id'));
            })
            ->with(['topics', 'section', 'options'])
            ->get()
            ->keyBy('id');
        $orderedQuestions = [];
        foreach ($order as $id) {
            $question = $questionsById->get($id);
            if ($question instanceof Question) {
                $orderedQuestions[] = $question;
            }
        }

        return new Collection($orderedQuestions);
    }

    /** @return Collection<int, Question> */
    private function questions(Resource $resource): Collection
    {
        return Question::query()->where(function ($query) use ($resource): void {
            $query->whereIn('listening_resource_id', $resource->listeningResource()->select('id'))
                ->orWhereIn('reading_resource_id', $resource->readingResource()->select('id'));
        })->orderBy('sort_order')->get();
    }
}
