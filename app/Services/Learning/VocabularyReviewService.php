<?php

namespace App\Services\Learning;

use App\Models\LearnerVocabularyItem;
use App\Models\LearningLesson;
use App\Models\Question;
use App\Models\Resource;
use App\Models\User;
use App\Models\VocabularyReviewEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VocabularyReviewService
{
    /**
     * Create review items when a learner encounters a published flashcard deck
     * or vocabulary-tagged interactive resource.
     */
    public function encounterResource(User $user, Resource $resource, bool $forceVocabulary = false): int
    {
        abort_unless($resource->status === 'published', 404);
        $resource->loadMissing(['skills', 'topics', 'cardResource.cards']);

        $created = 0;
        if ($resource->type === 'card' && $resource->cardResource !== null) {
            foreach ($resource->cardResource->cards as $card) {
                $created += $this->discover($user, $card->front, $card->back, 'resource_card', (int) $card->id, (int) $resource->id);
            }
        }

        if (($forceVocabulary || $this->isVocabularyResource($resource)) && in_array($resource->type, ['listening', 'reading'], true)) {
            $relation = $resource->type === 'listening' ? 'listeningResource.questions.options' : 'readingResource.questions.options';
            $resource->loadMissing($relation);
            $interactive = $resource->type === 'listening' ? $resource->listeningResource : $resource->readingResource;
            if ($interactive !== null) {
                foreach ($interactive->questions as $question) {
                    $created += $this->discoverQuestion($user, $question, (int) $resource->id);
                }
            }
        }

        return $created;
    }

    /**
     * Lesson activities count as encountered when the learner opens the lesson.
     * Only vocabulary-labelled resources/tests are converted into review items.
     */
    public function encounterLesson(User $user, LearningLesson $lesson): int
    {
        $lesson->loadMissing([
            'skills', 'topics',
            'activities.resource.skills', 'activities.resource.topics', 'activities.resource.cardResource.cards',
            'activities.resource.listeningResource.questions.options', 'activities.resource.readingResource.questions.options',
            'activities.test.skill', 'activities.test.sections.questions.options',
        ]);

        $lessonIsVocabulary = $lesson->skills->contains('slug', 'vocabulary')
            || $lesson->topics->contains('slug', 'vocabulary');
        $created = 0;

        foreach ($lesson->activities as $activity) {
            if ($activity->type === 'resource' && $activity->resource !== null) {
                $resource = $activity->resource;
                if ($resource->type === 'card' || $this->isVocabularyResource($resource) || $lessonIsVocabulary) {
                    $created += $this->encounterResource($user, $resource, $lessonIsVocabulary);
                }
            }

            if ($activity->type === 'test' && $activity->test !== null
                && ($lessonIsVocabulary || $activity->test->skill?->slug === 'vocabulary')) {
                foreach ($activity->test->sections->flatMap->questions as $question) {
                    $created += $this->discoverQuestion($user, $question, null);
                }
            }
        }

        return $created;
    }

    /**
     * @return Collection<int, LearnerVocabularyItem>
     */
    public function dueItems(User $user, int $limit = 30): Collection
    {
        return LearnerVocabularyItem::query()
            ->where('user_id', $user->id)
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, LearnerVocabularyItem>
     */
    public function upcomingItems(User $user, int $days = 7, int $limit = 30): Collection
    {
        return LearnerVocabularyItem::query()
            ->where('user_id', $user->id)
            ->where('due_at', '>', now())
            ->where('due_at', '<=', now()->addDays($days))
            ->orderBy('due_at')
            ->limit($limit)
            ->get();
    }

    public function review(User $user, LearnerVocabularyItem $item, string $grade, ?int $responseTimeMs = null): LearnerVocabularyItem
    {
        if (! in_array($grade, ['again', 'hard', 'good', 'easy'], true)) {
            throw ValidationException::withMessages(['grade' => 'Choose a valid review grade.']);
        }

        return DB::transaction(function () use ($user, $item, $grade, $responseTimeMs): LearnerVocabularyItem {
            $locked = LearnerVocabularyItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->user_id === $user->id, 404);

            $previousInterval = (int) $locked->interval_seconds;
            $ease = (float) $locked->ease_factor;
            $repetitions = (int) $locked->repetitions;
            $lapses = (int) $locked->lapses;

            if ($grade === 'again') {
                $nextInterval = 600;
                $repetitions = 0;
                $lapses++;
                $ease = max(1.3, $ease - 0.2);
            } else {
                $nextInterval = match ($grade) {
                    'hard' => max(86400, (int) round(max(86400, $previousInterval) * 1.2)),
                    'good' => match ($repetitions) {
                        0 => 86400,
                        1 => 259200,
                        default => max(86400, (int) round(max(86400, $previousInterval) * $ease)),
                    },
                    'easy' => match ($repetitions) {
                        0 => 345600,
                        1 => 604800,
                        default => max(86400, (int) round(max(86400, $previousInterval) * $ease * 1.3)),
                    },
                };
                $repetitions++;
                if ($grade === 'hard') {
                    $ease = max(1.3, $ease - 0.15);
                } elseif ($grade === 'easy') {
                    $ease = min(3.0, $ease + 0.15);
                }
            }

            $now = now();
            $locked->forceFill([
                'interval_seconds' => $nextInterval,
                'ease_factor' => round($ease, 2),
                'repetitions' => $repetitions,
                'lapses' => $lapses,
                'last_grade' => $grade,
                'last_reviewed_at' => $now,
                'due_at' => $now->copy()->addSeconds($nextInterval),
            ])->save();

            $event = VocabularyReviewEvent::query()->create([
                'learner_vocabulary_item_id' => $locked->id,
                'user_id' => $user->id,
                'grade' => $grade,
                'previous_interval_seconds' => $previousInterval,
                'next_interval_seconds' => $nextInterval,
                'response_time_ms' => $responseTimeMs,
                'reviewed_at' => $now,
            ]);
            app(EngagementService::class)->awardVocabularyReview($user, $event);

            return $locked->fresh();
        });
    }

    private function isVocabularyResource(Resource $resource): bool
    {
        return $resource->skills->contains('slug', 'vocabulary')
            || $resource->topics->contains('slug', 'vocabulary')
            || $resource->type === 'vocabulary';
    }

    private function discoverQuestion(User $user, Question $question, ?int $resourceId): int
    {
        $created = 0;
        if (in_array($question->type, ['text', 'short_text', 'single_choice', 'true_false'], true)) {
            $answers = $question->options->where('is_correct', true)->pluck('value')->filter()->values();
            if ($answers->isNotEmpty()) {
                $created += $this->discover(
                    $user,
                    $question->question,
                    $answers->implode(' / '),
                    'lesson_question',
                    (int) $question->id,
                    $resourceId,
                );
            }
        }

        if ($question->type === 'matching') {
            foreach ($question->metadata['pairs'] ?? [] as $pair) {
                if (! is_array($pair) || ! isset($pair['left'], $pair['right'])) {
                    continue;
                }
                $created += $this->discover($user, (string) $pair['left'], (string) $pair['right'], 'lesson_question', (int) $question->id, $resourceId);
            }
        }

        if ($question->type === 'gap_fill') {
            foreach ($question->metadata['gaps'] ?? [] as $gap => $acceptedAnswers) {
                $acceptedAnswers = is_array($acceptedAnswers) ? $acceptedAnswers : [$acceptedAnswers];
                $acceptedAnswers = array_values(array_filter($acceptedAnswers, 'is_string'));
                if ($acceptedAnswers === []) {
                    continue;
                }
                $created += $this->discover(
                    $user,
                    $question->question.' ('.$gap.')',
                    implode(' / ', $acceptedAnswers),
                    'lesson_question',
                    (int) $question->id,
                    $resourceId,
                );
            }
        }

        return $created;
    }

    private function discover(User $user, string $prompt, string $answer, string $sourceType, int $sourceId, ?int $resourceId): int
    {
        $prompt = trim(strip_tags($prompt));
        $answer = trim(strip_tags($answer));
        if ($prompt === '' || $answer === '') {
            return 0;
        }

        $normalise = fn (string $value): string => Str::lower(trim((string) preg_replace('/\s+/u', ' ', strip_tags($value))));
        $key = hash('sha256', $normalise($prompt)."\0".$normalise($answer));
        $item = LearnerVocabularyItem::query()->firstOrCreate(
            ['user_id' => $user->id, 'item_key' => $key],
            [
                'resource_id' => $resourceId,
                'source_id' => $sourceId,
                'source_type' => $sourceType,
                'prompt' => $prompt,
                'answer' => $answer,
                'due_at' => now(),
            ],
        );

        return $item->wasRecentlyCreated ? 1 : 0;
    }
}
