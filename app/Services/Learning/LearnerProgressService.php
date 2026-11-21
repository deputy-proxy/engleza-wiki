<?php

namespace App\Services\Learning;

use App\Models\AdaptivePracticeSession;
use App\Models\ClassGroup;
use App\Models\LearnerVocabularyItem;
use App\Models\LearningAttempt;
use App\Models\LearningLesson;
use App\Models\LearningPathEnrollment;
use App\Models\Question;
use App\Models\Resource;
use App\Models\TestAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class LearnerProgressService
{
    public const MINIMUM_UNIQUE_QUESTIONS = 5;

    public const MINIMUM_ATTEMPTS = 2;

    public const RECENCY_HALF_LIFE_DAYS = 60;

    /**
     * Derives a deterministic learner profile from immutable completed attempts.
     *
     * Question scores are normalised to 0..1 before aggregation. Evidence weight is
     * difficulty × recency decay × repeat discount. A topic/skill is not assigned a
     * mastery percentage until it has five distinct questions across two attempts.
     *
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $testAttempts = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with(['test.translations', 'test.level.translations', 'test.skill.translations', 'answers.question.translations', 'answers.question.topics.translations'])
            ->orderByDesc('completed_at')
            ->get();

        $adaptiveSessions = AdaptivePracticeSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with([
                'answers.question.translations',
                'answers.question.topics.translations',
                'answers.question.section.translations',
                'answers.question.section.test.translations',
                'answers.question.section.test.level.translations',
                'answers.question.section.test.skill.translations',
                'answers.question.readingResource.translations',
                'answers.question.readingResource.resource.translations',
                'answers.question.readingResource.resource.skills.translations',
                'answers.question.readingResource.resource.topics.translations',
                'answers.question.listeningResource.translations',
                'answers.question.listeningResource.resource.translations',
                'answers.question.listeningResource.resource.skills.translations',
                'answers.question.listeningResource.resource.topics.translations',
            ])
            ->orderByDesc('completed_at')
            ->get();

        $learningAttempts = LearningAttempt::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with(['resource.translations', 'resource.level.translations', 'resource.skills.translations', 'resource.topics.translations', 'answers.question.translations', 'answers.question.topics.translations'])
            ->orderByDesc('completed_at')
            ->get();

        $skillEvidence = [];
        $topicEvidence = [];

        foreach ($testAttempts as $attempt) {
            $test = $attempt->test;

            foreach ($attempt->answers as $answer) {
                $question = $answer->question;
                $score = $this->normalisedAnswerScore($answer->points_awarded, $answer->is_correct, $question);
                if ($score === null) {
                    continue;
                }

                if ($test->skill !== null) {
                    $this->addEvidence($skillEvidence, (int) $test->skill->id, $test->skill->translated('name'), $score, $question, $attempt->completed_at, 'test:'.$attempt->id);
                }

                foreach ($question->topics as $topic) {
                    $this->addEvidence($topicEvidence, (int) $topic->id, __($topic->translated('name')), $score, $question, $attempt->completed_at, 'test:'.$attempt->id);
                }
            }
        }

        foreach ($learningAttempts as $attempt) {
            $resource = $attempt->resource;

            foreach ($attempt->answers as $answer) {
                $question = $answer->question;
                $score = $this->normalisedAnswerScore($answer->points_awarded, $answer->is_correct, $question);
                if ($score === null) {
                    continue;
                }

                foreach ($resource->skills as $skill) {
                    $this->addEvidence($skillEvidence, (int) $skill->id, __($skill->translated('name')), $score, $question, $attempt->completed_at, 'learning:'.$attempt->id);
                }

                $topics = $question->topics->isNotEmpty() ? $question->topics : $resource->topics;
                foreach ($topics as $topic) {
                    $this->addEvidence($topicEvidence, (int) $topic->id, __($topic->translated('name')), $score, $question, $attempt->completed_at, 'learning:'.$attempt->id);
                }
            }
        }

        foreach ($adaptiveSessions as $session) {
            foreach ($session->answers as $answer) {
                $question = $answer->question;
                $score = $this->normalisedAnswerScore($answer->points_awarded, $answer->is_correct, $question);
                if ($score === null) {
                    continue;
                }

                $testSkill = $question->section?->test?->skill;
                if ($testSkill !== null) {
                    $this->addEvidence($skillEvidence, (int) $testSkill->id, __($testSkill->translated('name')), $score, $question, $session->completed_at, 'adaptive:'.$session->id, 0.75);
                }

                $resource = null;
                if ($question->readingResource !== null) {
                    $resource = $question->readingResource->resource;
                } elseif ($question->listeningResource !== null) {
                    $resource = $question->listeningResource->resource;
                }
                foreach ($resource->skills ?? collect() as $skill) {
                    $this->addEvidence($skillEvidence, (int) $skill->id, __($skill->translated('name')), $score, $question, $session->completed_at, 'adaptive:'.$session->id, 0.75);
                }

                $topics = $question->topics->isNotEmpty() ? $question->topics : ($resource->topics ?? collect());
                foreach ($topics as $topic) {
                    $this->addEvidence($topicEvidence, (int) $topic->id, __($topic->translated('name')), $score, $question, $session->completed_at, 'adaptive:'.$session->id, 0.75);
                }
            }
        }

        $skills = $this->aggregate($skillEvidence);
        $topics = $this->aggregate($topicEvidence);
        $latestTest = $testAttempts->first();
        $completedLessons = $user->lessonProgress()->where('status', 'completed')->count();
        $startedLessons = $user->lessonProgress()->where('status', 'started')->count();
        $reviewDue = LearnerVocabularyItem::query()->where('user_id', $user->id)->where('due_at', '<=', now())->count();
        $learningPaths = $this->learningPathProgress($user);

        return [
            'cefr' => [
                'level' => $latestTest?->level_result ?: $latestTest?->test?->level?->translated('name'),
                'assessed_at' => $latestTest?->completed_at,
                'source' => $latestTest === null ? null : 'latest_completed_formal_test',
            ],
            'skills' => $skills,
            'topics' => $topics,
            'learning_paths' => $learningPaths,
            'stats' => [
                'completed_tests' => $testAttempts->count(),
                'completed_exercises' => $learningAttempts->count(),
                'completed_adaptive_sessions' => $adaptiveSessions->count(),
                'completed_lessons' => $completedLessons,
                'lessons_in_progress' => $startedLessons,
                'reviews_due' => $reviewDue,
                'evidence_questions' => collect($skills)->sum('unique_questions') + collect($topics)->sum('unique_questions'),
            ],
            'recent_activity' => $this->recentActivity($testAttempts, $learningAttempts, $adaptiveSessions, $user),
            'recommendations' => $this->recommendations($user, $topics, $learningPaths),
        ];
    }

    /**
     * Teacher summaries are deliberately restricted to completed attempts attached
     * to this class group. No unrelated learner history is included.
     *
     * @return array<int, array<string, mixed>>
     */
    public function classGroupSummary(ClassGroup $group): array
    {
        $attempts = TestAttempt::query()
            ->where('class_group_id', $group->id)
            ->whereNotNull('user_id')
            ->whereHas('user.classGroupMemberships', fn ($query) => $query->where('class_group_id', $group->id)->whereNull('removed_at'))
            ->where('status', 'completed')
            ->with(['user:id,name', 'test.translations', 'test.skill.translations', 'answers.question.translations', 'answers.question.topics.translations'])
            ->orderBy('completed_at')
            ->get()
            ->groupBy('user_id');

        $learners = [];
        foreach ($attempts as $userId => $learnerAttempts) {
            $skillScores = [];
            $topicScores = [];
            $questionIds = [];
            $earned = 0.0;
            $possible = 0.0;

            foreach ($learnerAttempts as $attempt) {
                foreach ($attempt->answers as $answer) {
                    $question = $answer->question;
                    if ((float) $question->points <= 0) {
                        continue;
                    }
                    if ($answer->points_awarded === null && $answer->is_correct === null) {
                        continue;
                    }
                    $ratio = $answer->points_awarded !== null
                        ? max(0.0, min(1.0, (float) $answer->points_awarded / (float) $question->points))
                        : ((bool) $answer->is_correct ? 1.0 : 0.0);
                    $questionIds[$question->id] = true;
                    $earned += $ratio * (float) $question->points;
                    $possible += (float) $question->points;

                    if ($attempt->test->skill !== null) {
                        $id = (int) $attempt->test->skill->id;
                        $skillScores[$id] ??= ['name' => __($attempt->test->skill->translated('name')), 'earned' => 0.0, 'total' => 0.0];
                        $skillScores[$id]['earned'] += $ratio * (float) $question->points;
                        $skillScores[$id]['total'] += (float) $question->points;
                    }

                    foreach ($question->topics as $topic) {
                        $id = (int) $topic->id;
                        $topicScores[$id] ??= ['name' => __($topic->translated('name')), 'earned' => 0.0, 'total' => 0.0];
                        $topicScores[$id]['earned'] += $ratio * (float) $question->points;
                        $topicScores[$id]['total'] += (float) $question->points;
                    }
                }
            }

            $learners[] = [
                'user' => $learnerAttempts->first()->user,
                'attempt_count' => $learnerAttempts->count(),
                'question_count' => count($questionIds),
                'percentage' => $possible > 0 ? round(($earned / $possible) * 100, 1) : null,
                'last_activity' => $learnerAttempts->last()->completed_at,
                'skills' => $this->percentageRows($skillScores),
                'topics' => $this->percentageRows($topicScores),
            ];
        }

        return $learners;
    }

    /**
     * @param  array<int, array{earned: float, total: float, name: string}>  $scores
     * @return array<int, array{name: string, percentage: float}>
     */
    private function percentageRows(array $scores): array
    {
        $rows = [];
        foreach ($scores as $score) {
            $rows[] = [
                'name' => $score['name'],
                'percentage' => $score['total'] > 0 ? round(($score['earned'] / $score['total']) * 100, 1) : 0.0,
            ];
        }

        usort($rows, fn (array $left, array $right): int => $left['percentage'] <=> $right['percentage']);

        return $rows;
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $buckets
     */
    private function addEvidence(
        array &$buckets,
        int $dimensionId,
        string $name,
        float $score,
        Question $question,
        ?Carbon $completedAt,
        string $attemptId,
        float $sourceWeight = 1.0,
    ): void {
        $buckets[$dimensionId] ??= [];
        $buckets[$dimensionId][] = [
            'name' => $name,
            'score' => $score,
            'question_id' => (int) $question->id,
            'attempt_id' => $attemptId,
            'source_weight' => $sourceWeight,
            'difficulty' => strtolower((string) $question->difficulty),
            'completed_at' => $completedAt ?? now(),
        ];
    }

    private function normalisedAnswerScore(mixed $pointsAwarded, mixed $isCorrect, Question $question): ?float
    {
        if ((float) $question->points <= 0) {
            return null;
        }

        if ($pointsAwarded !== null) {
            return max(0.0, min(1.0, (float) $pointsAwarded / (float) $question->points));
        }

        if ($isCorrect !== null) {
            return (bool) $isCorrect ? 1.0 : 0.0;
        }

        return null;
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $evidence
     * @return array<int, array<string, mixed>>
     */
    private function aggregate(array $evidence): array
    {
        $results = [];

        foreach ($evidence as $id => $observations) {
            $questionCounts = collect($observations)->countBy('question_id');
            $weightedScore = 0.0;
            $totalWeight = 0.0;
            $attemptIds = [];
            $questionIds = [];

            foreach ($observations as $observation) {
                /** @var Carbon $completedAt */
                $completedAt = $observation['completed_at'];
                $ageDays = max(0, $completedAt->diffInDays(now()));
                $recencyWeight = pow(0.5, $ageDays / self::RECENCY_HALF_LIFE_DAYS);
                $difficultyWeight = match ($observation['difficulty']) {
                    'easy' => 0.85,
                    'hard' => 1.15,
                    default => 1.0,
                };
                $repeatCount = (int) $questionCounts->get($observation['question_id'], 1);
                $repeatWeight = 1 / sqrt(max(1, $repeatCount));
                $weight = $recencyWeight * $difficultyWeight * $repeatWeight * (float) ($observation['source_weight'] ?? 1.0);

                $weightedScore += $observation['score'] * $weight;
                $totalWeight += $weight;
                $attemptIds[$observation['attempt_id']] = true;
                $questionIds[$observation['question_id']] = true;
            }

            $uniqueQuestions = count($questionIds);
            $attemptCount = count($attemptIds);
            $observed = $totalWeight > 0 ? round(($weightedScore / $totalWeight) * 100, 1) : null;
            $hasEnoughEvidence = $uniqueQuestions >= self::MINIMUM_UNIQUE_QUESTIONS
                && $attemptCount >= self::MINIMUM_ATTEMPTS;

            $results[] = [
                'id' => (int) $id,
                'name' => $observations[0]['name'],
                'mastery_percentage' => $hasEnoughEvidence ? $observed : null,
                'observed_percentage' => $observed,
                'mastery_state' => ! $hasEnoughEvidence
                    ? 'insufficient_evidence'
                    : ($observed < 60 ? 'needs_practice' : ($observed < 80 ? 'developing' : 'strong')),
                'confidence' => ! $hasEnoughEvidence
                    ? 'insufficient'
                    : (($uniqueQuestions >= 10 && $attemptCount >= 3) ? 'high' : 'moderate'),
                'unique_questions' => $uniqueQuestions,
                'attempt_count' => $attemptCount,
            ];
        }

        usort($results, function (array $left, array $right): int {
            $leftScore = $left['mastery_percentage'] ?? 101;
            $rightScore = $right['mastery_percentage'] ?? 101;

            return $leftScore <=> $rightScore;
        });

        return $results;
    }

    /**
     * @param  EloquentCollection<int, TestAttempt>  $testAttempts
     * @param  EloquentCollection<int, LearningAttempt>  $learningAttempts
     * @param  EloquentCollection<int, AdaptivePracticeSession>  $adaptiveSessions
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(EloquentCollection $testAttempts, EloquentCollection $learningAttempts, EloquentCollection $adaptiveSessions, User $user): array
    {
        $activity = collect();

        foreach ($testAttempts->take(5) as $attempt) {
            $activity->push([
                'type' => 'test',
                'title' => $attempt->test->translated('title'),
                'date' => $attempt->completed_at,
                'detail' => __(':percentage% score', ['percentage' => round((float) $attempt->percentage, 1)]),
                'url' => route('tests.result', [$attempt->test, $attempt]),
            ]);
        }

        foreach ($learningAttempts->take(5) as $attempt) {
            $activity->push([
                'type' => 'exercise',
                'title' => $attempt->resource->translated('title'),
                'date' => $attempt->completed_at,
                'detail' => __(':percentage% score', ['percentage' => round((float) $attempt->percentage, 1)]),
                'url' => route('resources.result', [$attempt->resource, $attempt]),
            ]);
        }

        foreach ($adaptiveSessions->take(5) as $session) {
            $activity->push([
                'type' => 'practice',
                'title' => __('Adaptive practice'),
                'date' => $session->completed_at,
                'detail' => __(':percentage% score', ['percentage' => round((float) $session->percentage, 1)]),
                'url' => route('practice.show', $session),
            ]);
        }

        $lessonProgress = $user->lessonProgress()->with('lesson.translations')->latest('updated_at')->limit(5)->get();
        foreach ($lessonProgress as $progress) {
            $activity->push([
                'type' => 'lesson',
                'title' => $progress->lesson->translated('title'),
                'date' => $progress->completed_at ?? $progress->updated_at,
                'detail' => $progress->status === 'completed' ? __('Lesson completed') : __('Lesson in progress'),
                'url' => route('learning.lessons.show', $progress->lesson),
            ]);
        }

        return $activity->sortByDesc(fn (array $item) => $item['date']->timestamp)->take(8)->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function learningPathProgress(User $user): array
    {
        $enrollments = LearningPathEnrollment::query()
            ->where('user_id', $user->id)
            ->with([
                'path.translations',
                'path.level.translations',
                'path.skills.translations',
                'path.topics.translations',
                'path.units.lessons.translations',
                'path.units.lessons.unit.path.translations',
                'path.units.lessons.activities.translations',
                'path.units.lessons.activities.resource.translations',
                'path.units.lessons.activities.resource.level.translations',
                'path.units.lessons.activities.resource.listeningResource.questions.translations',
                'path.units.lessons.activities.resource.listeningResource.questions.options',
                'path.units.lessons.activities.resource.listeningResource.questions.topics.translations',
                'path.units.lessons.activities.resource.readingResource.questions.translations',
                'path.units.lessons.activities.resource.readingResource.questions.options',
                'path.units.lessons.activities.resource.readingResource.questions.topics.translations',
                'path.units.lessons.activities.test.translations',
                'path.units.lessons.activities.test.level.translations',
                'path.units.lessons.activities.test.skill.translations',
                'path.units.lessons.activities.test.sections.questions.translations',
                'path.units.lessons.activities.test.sections.questions.options',
                'path.units.lessons.activities.test.sections.questions.topics.translations',
                'path.units.lessons.progress' => fn ($query) => $query->where('user_id', $user->id),
            ])
            ->latest('activated_at')
            ->get();

        $summaries = [];
        foreach ($enrollments as $enrollment) {
            $path = $enrollment->path;
            if ($path === null) {
                continue;
            }

            $availableLessons = $path->units->where('is_published', true)
                ->flatMap(fn ($unit) => $unit->lessons)
                ->filter(fn (LearningLesson $lesson): bool => app(LearningPathService::class)->isLessonAvailable($lesson, true))
                ->values();
            $completedCount = $availableLessons->filter(fn (LearningLesson $lesson): bool => $lesson->progress
                ->contains(fn ($progress): bool => $progress->status === 'completed'))->count();
            $nextLesson = $availableLessons->first(fn (LearningLesson $lesson): bool => ! $lesson->progress
                ->contains(fn ($progress): bool => $progress->status === 'completed'));

            $summaries[] = [
                'title' => $path->translated('title'),
                'level' => $path->level?->translated('name'),
                'status' => $enrollment->status,
                'completed_lessons' => $completedCount,
                'available_lessons' => $availableLessons->count(),
                'percentage' => $availableLessons->isNotEmpty()
                    ? round(($completedCount / $availableLessons->count()) * 100, 1) : 0.0,
                'url' => route('learning.paths.show', $path),
                'next_lesson' => $nextLesson === null ? null : [
                    'title' => $nextLesson->translated('title'),
                    'url' => route('learning.lessons.show', $nextLesson),
                    'objectives' => $nextLesson->translated('objectives') ?? [],
                ],
            ];
        }

        return $summaries;
    }

    /**
     * @param  array<int, array<string, mixed>>  $topics
     * @param  array<int, array<string, mixed>>  $learningPaths
     * @return array<int, array<string, mixed>>
     */
    private function recommendations(User $user, array $topics, array $learningPaths): array
    {
        $recommendations = [];
        $weakTopics = collect($topics)->where('mastery_state', 'needs_practice')->take(3)->values();

        if ($weakTopics->isNotEmpty()) {
            $weakTopicIds = $weakTopics->pluck('id');
            $recentResourcesByTopic = [];

            LearningAttempt::query()
                ->where('user_id', $user->id)
                ->where('completed_at', '>=', now()->subDays(14))
                ->whereHas('resource.topics', fn ($query) => $query->whereIn('topics.id', $weakTopicIds))
                ->with('resource.topics:id')
                ->get(['id', 'resource_id'])
                ->each(function (LearningAttempt $attempt) use (&$recentResourcesByTopic): void {
                    foreach ($attempt->resource->topics as $topic) {
                        $recentResourcesByTopic[$topic->id][$attempt->resource_id] = true;
                    }
                });

            $resourcesByTopic = [];
            Resource::query()->published()
                ->whereHas('topics', fn ($query) => $query->whereIn('topics.id', $weakTopicIds))
                ->with(['translations', 'level.translations', 'topics:id'])
                ->chunkById(100, function ($candidateResources) use ($weakTopics, $recentResourcesByTopic, &$resourcesByTopic): bool {
                    foreach ($weakTopics as $topic) {
                        if (isset($resourcesByTopic[$topic['id']])) {
                            continue;
                        }

                        $recentResourceIds = $recentResourcesByTopic[$topic['id']] ?? [];
                        $resource = $candidateResources->first(fn (Resource $candidate): bool => $candidate->topics->contains('id', $topic['id'])
                            && ! isset($recentResourceIds[$candidate->id])
                        );

                        if ($resource !== null) {
                            $resourcesByTopic[$topic['id']] = $resource;
                        }
                    }

                    return count($resourcesByTopic) < $weakTopics->count();
                });

            foreach ($weakTopics as $topic) {
                $resource = $resourcesByTopic[$topic['id']] ?? null;
                if ($resource instanceof Resource) {
                    $recommendations[] = [
                        'type' => 'topic',
                        'title' => $resource->translated('title'),
                        'reason' => __('Practise :topic (:percentage% demonstrated mastery).', ['topic' => $topic['name'], 'percentage' => $topic['mastery_percentage']]),
                        'url' => route('resources.show', $resource),
                    ];
                }
            }
        }

        if (LearnerVocabularyItem::query()->where('user_id', $user->id)->where('due_at', '<=', now())->exists()) {
            $recommendations[] = [
                'type' => 'review',
                'title' => __('Review due vocabulary'),
                'reason' => __('You have vocabulary items ready for spaced review.'),
                'url' => route('reviews.index'),
            ];
        }

        foreach ($learningPaths as $path) {
            if ($path['next_lesson'] !== null) {
                $recommendations[] = [
                    'type' => 'lesson',
                    'title' => $path['next_lesson']['title'],
                    'reason' => __('Continue your active learning path: :title.', ['title' => $path['title']]),
                    'url' => $path['next_lesson']['url'],
                ];
                break;
            }
        }

        return array_slice($recommendations, 0, 5);
    }
}
