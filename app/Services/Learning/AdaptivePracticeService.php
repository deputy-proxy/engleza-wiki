<?php

namespace App\Services\Learning;

use App\Models\AdaptivePracticeAnswer;
use App\Models\AdaptivePracticeSession;
use App\Models\Question;
use App\Models\User;
use App\Services\Assessment\QuestionResponseService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdaptivePracticeService
{
    private const QUESTION_LIMIT = 10;

    private const RECENT_QUESTION_DAYS = 14;

    public function __construct(
        private readonly LearnerProgressService $progress,
        private readonly QuestionResponseService $responses,
    ) {}

    public function start(User $user): AdaptivePracticeSession
    {
        $profile = $this->progress->summary($user);
        [$minimum, $maximum] = $this->difficultyBounds($profile);
        /** @var array<int, array<string, mixed>> $profileTopics */
        $profileTopics = $profile['topics'] ?? [];
        $weakTopics = collect($profileTopics)
            ->filter(fn (array $topic): bool => $topic['mastery_percentage'] !== null)
            ->sortBy('mastery_percentage')
            ->take(5)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $recentQuestionIds = $this->recentQuestionIds($user);
        $questions = $this->eligibleQuestions($recentQuestionIds);
        $rank = ['easy' => 1, 'medium' => 2, 'hard' => 3];
        $minRank = $rank[$minimum] ?? 1;
        $maxRank = $rank[$maximum] ?? 2;
        $questions = $questions->filter(function (Question $question) use ($rank, $minRank, $maxRank): bool {
            $difficultyRank = $rank[strtolower((string) $question->difficulty)] ?? 2;

            return $difficultyRank >= $minRank && $difficultyRank <= $maxRank;
        })->values();
        if ($questions->isEmpty()) {
            // If the learner has seen all eligible questions recently, allow a
            // deliberate revisit rather than returning an empty practice session.
            $questions = $this->eligibleQuestions([])->filter(function (Question $question) use ($rank, $minRank, $maxRank): bool {
                $difficultyRank = $rank[strtolower((string) $question->difficulty)] ?? 2;

                return $difficultyRank >= $minRank && $difficultyRank <= $maxRank;
            })->values();
        }

        $ranked = $questions->map(function (Question $question) use ($weakTopics, $minimum, $maximum): array {
            $difficulty = strtolower((string) $question->difficulty);
            $difficultyRank = ['easy' => 1, 'medium' => 2, 'hard' => 3][$difficulty] ?? 2;
            $minRank = ['easy' => 1, 'medium' => 2, 'hard' => 3][$minimum] ?? 1;
            $maxRank = ['easy' => 1, 'medium' => 2, 'hard' => 3][$maximum] ?? 2;
            $topics = $question->topics->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $topicPriority = count(array_intersect($topics, $weakTopics)) > 0 ? 0 : 1;
            $difficultyPenalty = $difficultyRank < $minRank || $difficultyRank > $maxRank ? 2 : 0;
            $targetRank = $minRank === $maxRank ? $minRank : (int) round(($minRank + $maxRank) / 2);

            return [
                'question' => $question,
                'priority' => ($topicPriority * 10) + $difficultyPenalty + abs($difficultyRank - $targetRank),
            ];
        })->sortBy('priority')->take(self::QUESTION_LIMIT)->values();

        $questionOrder = $ranked->pluck('question.id')->map(fn ($id): int => (int) $id)->all();
        abort_if($questionOrder === [], 422, 'There are no published practice questions available right now.');

        return AdaptivePracticeSession::query()->create([
            'user_id' => $user->id,
            'question_order' => $questionOrder,
            'status' => 'started',
            'difficulty_min' => $minimum,
            'difficulty_max' => $maximum,
            'started_at' => now(),
        ]);
    }

    public function session(AdaptivePracticeSession $session, User $user): AdaptivePracticeSession
    {
        abort_unless($session->user_id === $user->id, 404);

        return $session->load(['answers.question']);
    }

    public function recordResponse(AdaptivePracticeSession $session, User $user, int $questionId, mixed $response): bool
    {
        return DB::transaction(function () use ($session, $user, $questionId, $response): bool {
            $locked = AdaptivePracticeSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->user_id === $user->id, 404);
            abort_unless($locked->status === 'started', 410);
            $questionOrder = $locked->question_order;
            abort_unless(in_array($questionId, $questionOrder, true), 422);

            $question = $this->questionsForSession($locked)->firstWhere('id', $questionId);
            abort_unless($question instanceof Question, 422);
            $evaluation = $this->responses->evaluate($question, $response);
            if (! $evaluation['valid']) {
                return false;
            }

            AdaptivePracticeAnswer::query()->updateOrCreate(
                ['adaptive_practice_session_id' => $locked->id, 'question_id' => $questionId],
                [
                    'answer_payload' => $evaluation['normalized'],
                    'is_correct' => $evaluation['correct'],
                    'points_awarded' => $evaluation['earned_points'],
                    'answered_at' => now(),
                ],
            );

            return true;
        });
    }

    public function complete(AdaptivePracticeSession $session, User $user): AdaptivePracticeSession
    {
        return DB::transaction(function () use ($session, $user): AdaptivePracticeSession {
            $locked = AdaptivePracticeSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->user_id === $user->id, 404);
            if ($locked->status === 'completed') {
                return $locked;
            }
            abort_unless($locked->status === 'started', 410);

            $questions = $this->questionsForSession($locked);
            $answers = $locked->answers()->get()->keyBy('question_id');
            $total = (float) $questions->sum(fn (Question $question): float => (float) $question->points);
            $earned = 0.0;
            foreach ($questions as $question) {
                $answer = $answers->get($question->id);
                if ($answer !== null) {
                    $earned += (float) $answer->points_awarded;
                }
            }

            $locked->forceFill([
                'earned_points' => $earned,
                'total_points' => (int) $total,
                'percentage' => $total > 0 ? round(($earned / $total) * 100, 1) : 0,
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();
            app(EngagementService::class)->awardPracticeCompletion($user, $locked);

            return $locked->fresh();
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function questionPayload(AdaptivePracticeSession $session): array
    {
        $reveal = $session->status === 'completed';
        $answers = $session->answers->keyBy('question_id');

        return $this->questionsForSession($session)->map(function (Question $question) use ($reveal, $answers): array {
            $answer = $answers->get($question->id);

            return [
                'id' => (int) $question->id,
                'text' => $question->question,
                'type' => $question->type,
                'difficulty' => $question->difficulty,
                'configuration' => $this->responses->learnerConfiguration($question),
                'options' => $question->options->map(fn ($option): array => [
                    'id' => (int) $option->id,
                    'label' => $option->label,
                    'value' => $option->value,
                    ...($reveal ? ['is_correct' => (bool) $option->is_correct] : []),
                ])->all(),
                'answer_key' => $reveal ? $this->responses->answerKey($question) : null,
                'explanation' => $reveal ? $question->translated('explanation') : null,
                'saved_answer' => $answer?->answer_payload,
                'is_correct' => $reveal ? $answer?->is_correct : null,
            ];
        })->all();
    }

    /**
     * @param  array<int, int>  $recentQuestionIds
     * @return Collection<int, Question>
     */
    private function eligibleQuestions(array $recentQuestionIds): Collection
    {
        return Question::query()
            ->where(function ($query): void {
                $query->whereHas('section.test', function ($test): void {
                    $test->where('status', 'published')->where('is_public', true)
                        ->whereIn('access_type', ['free', 'registered'])
                        ->where(fn ($published) => $published->whereNull('published_at')->orWhere('published_at', '<=', now()));
                })->orWhereHas('readingResource.resource', fn ($resource) => $resource->published())
                    ->orWhereHas('listeningResource.resource', fn ($resource) => $resource->published());
            })
            ->when($recentQuestionIds !== [], fn ($query) => $query->whereNotIn('questions.id', $recentQuestionIds))
            ->with(['options', 'topics', 'section.test', 'readingResource.resource', 'listeningResource.resource'])
            ->orderBy('questions.id')
            ->get()
            ->filter(fn (Question $question): bool => $this->responses->definitionIssues($question) === [])
            ->values();
    }

    /**
     * @return Collection<int, Question>
     */
    private function questionsForSession(AdaptivePracticeSession $session): Collection
    {
        /** @var array<int, int> $order */
        $order = $session->questionIds();
        $byId = Question::query()->whereIn('id', $order)
            ->with(['options', 'topics', 'section.test', 'readingResource.resource', 'listeningResource.resource'])
            ->get()->keyBy('id');

        return collect($order)->map(fn ($id) => $byId->get($id))
            ->filter(fn ($question): bool => $question instanceof Question)
            ->values();
    }

    /**
     * @return array<int, int>
     */
    private function recentQuestionIds(User $user): array
    {
        $since = now()->subDays(self::RECENT_QUESTION_DAYS);

        $testIds = DB::table('test_answers')
            ->join('test_attempts', 'test_attempts.id', '=', 'test_answers.test_attempt_id')
            ->where('test_attempts.user_id', $user->id)
            ->where('test_attempts.started_at', '>=', $since)
            ->pluck('test_answers.question_id');

        $learningIds = DB::table('learning_answers')
            ->join('learning_attempts', 'learning_attempts.id', '=', 'learning_answers.learning_attempt_id')
            ->where('learning_attempts.user_id', $user->id)
            ->where('learning_attempts.started_at', '>=', $since)
            ->pluck('learning_answers.question_id');

        $adaptiveIds = DB::table('adaptive_practice_answers')
            ->join('adaptive_practice_sessions', 'adaptive_practice_sessions.id', '=', 'adaptive_practice_answers.adaptive_practice_session_id')
            ->where('adaptive_practice_sessions.user_id', $user->id)
            ->where('adaptive_practice_sessions.started_at', '>=', $since)
            ->pluck('adaptive_practice_answers.question_id');

        return $testIds->merge($learningIds)->merge($adaptiveIds)->unique()->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array{0: string, 1: string}
     */
    private function difficultyBounds(array $profile): array
    {
        $level = strtoupper((string) ($profile['cefr']['level'] ?? ''));
        $beginner = str_contains($level, 'A1') || str_contains($level, 'A2');
        $intermediate = str_contains($level, 'B1') || str_contains($level, 'B2');
        $advanced = str_contains($level, 'C1') || str_contains($level, 'C2');
        /** @var array<int, array<string, mixed>> $profileTopics */
        $profileTopics = $profile['topics'] ?? [];
        $weakest = collect($profileTopics)->whereNotNull('mastery_percentage')->sortBy('mastery_percentage')->first();
        $mastery = $weakest['mastery_percentage'] ?? null;

        if ($level === '' || ($mastery !== null && $mastery < 55) || $beginner) {
            return ['easy', 'medium'];
        }

        if ($advanced) {
            return ['medium', 'hard'];
        }

        if ($intermediate) {
            return [$mastery !== null && $mastery >= 80 ? 'medium' : 'easy', 'hard'];
        }

        return ['easy', 'medium'];
    }
}
