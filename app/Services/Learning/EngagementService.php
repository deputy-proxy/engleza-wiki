<?php

namespace App\Services\Learning;

use App\Models\AdaptivePracticeSession;
use App\Models\ExperiencePointAward;
use App\Models\LearnerAchievement;
use App\Models\LearnerDailyEngagement;
use App\Models\LearnerEngagementSetting;
use App\Models\LessonProgress;
use App\Models\User;
use App\Models\VocabularyReviewEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EngagementService
{
    public function settings(User $user): LearnerEngagementSetting
    {
        return LearnerEngagementSetting::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['daily_goal_xp' => 20, 'timezone' => 'UTC'],
        );
    }

    public function updateSettings(User $user, int $dailyGoalXp, string $timezone): LearnerEngagementSetting
    {
        if (! in_array($dailyGoalXp, config('engagement.daily_goals', [10, 20, 30, 50]), true)) {
            throw ValidationException::withMessages(['daily_goal_xp' => 'Choose a daily goal of 10, 20, 30 or 50 XP.']);
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw ValidationException::withMessages(['timezone' => 'Choose a valid timezone identifier.']);
        }

        $settings = $this->settings($user);
        $settings->update(['daily_goal_xp' => $dailyGoalXp, 'timezone' => $timezone]);

        return $settings->fresh();
    }

    public function awardLessonCompletion(int $userId, int $lessonId): ?ExperiencePointAward
    {
        return $this->award($userId, 'lesson', $lessonId, 'lesson:'.$userId.':'.$lessonId, (int) config('engagement.xp.lesson_completed', 20));
    }

    public function awardPracticeCompletion(User $user, AdaptivePracticeSession $session): ?ExperiencePointAward
    {
        return $this->award(
            $user->id, 'adaptive_practice', $session->id, 'adaptive-practice:'.$user->id.':'.$session->id,
            (int) config('engagement.xp.adaptive_practice_completed', 10),
            (int) config('engagement.daily_limits.adaptive_practice', 3),
        );
    }

    public function awardVocabularyReview(User $user, VocabularyReviewEvent $event): ?ExperiencePointAward
    {
        $settings = $this->settings($user);
        $date = CarbonImmutable::parse($event->reviewed_at)->setTimezone($settings->timezone)->toDateString();

        return $this->award(
            $user->id,
            'vocabulary_review',
            $event->id,
            'vocabulary-review:'.$user->id.':'.$event->learner_vocabulary_item_id.':'.$date,
            (int) config('engagement.xp.vocabulary_review_completed', 2),
            (int) config('engagement.daily_limits.vocabulary_review', 10),
            ['grade' => $event->grade],
            CarbonImmutable::parse($event->reviewed_at),
        );
    }

    /** @return array<string, mixed> */
    public function summary(User $user): array
    {
        $settings = $this->settings($user);
        $today = CarbonImmutable::now($settings->timezone)->toDateString();
        $daily = LearnerDailyEngagement::query()->where('user_id', $user->id)
            ->whereDate('activity_date', $today)->first();
        $goalTarget = $daily ? $daily->goal_xp : $settings->daily_goal_xp;
        $goalEarned = $daily ? $daily->xp_earned : 0;
        $xp = (int) ExperiencePointAward::query()->where('user_id', $user->id)->sum('points');
        $streak = $this->currentStreak($user, $settings->timezone);

        return [
            'xp_total' => $xp,
            'daily_goal' => [
                'target' => $goalTarget,
                'earned' => $goalEarned,
                'remaining' => max(0, $goalTarget - $goalEarned),
                'percentage' => min(100, (int) round(($goalEarned / max(1, $goalTarget)) * 100)),
                'completed' => $daily?->goal_completed_at !== null,
            ],
            'current_streak' => $streak,
            'timezone' => $settings->timezone,
            'daily_goal_xp' => $settings->daily_goal_xp,
            'achievements' => LearnerAchievement::query()->where('user_id', $user->id)->orderByDesc('awarded_at')->get(),
            'available_achievements' => config('engagement.achievements', []),
            'recent_awards' => ExperiencePointAward::query()->where('user_id', $user->id)->latest('occurred_at')->limit(8)->get(),
            'leaderboards_enabled' => false,
        ];
    }

    /** @param array<string, mixed> $metadata */
    private function award(
        int $userId,
        string $sourceType,
        ?int $sourceId,
        string $idempotencyKey,
        int $points,
        ?int $dailyLimit = null,
        array $metadata = [],
        ?CarbonImmutable $occurredAt = null,
    ): ?ExperiencePointAward {
        return DB::transaction(function () use ($userId, $sourceType, $sourceId, $idempotencyKey, $points, $dailyLimit, $metadata, $occurredAt): ?ExperiencePointAward {
            $user = User::query()->findOrFail($userId);
            $this->settings($user);
            $settings = LearnerEngagementSetting::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
            $occurredAt ??= CarbonImmutable::now();
            $activityDate = $occurredAt->setTimezone($settings->timezone)->toDateString();

            if (ExperiencePointAward::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return ExperiencePointAward::query()->where('idempotency_key', $idempotencyKey)->first();
            }

            if ($dailyLimit !== null) {
                $awardsToday = ExperiencePointAward::query()->where('user_id', $userId)
                    ->where('source_type', $sourceType)->where('activity_date', $activityDate)->count();
                if ($awardsToday >= $dailyLimit) {
                    return null;
                }
            }

            $inserted = DB::table('experience_point_awards')->insertOrIgnore([
                'user_id' => $userId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'idempotency_key' => $idempotencyKey,
                'points' => $points,
                'activity_date' => $activityDate,
                'occurred_at' => $occurredAt,
                'metadata' => $metadata === [] ? null : json_encode($metadata, JSON_THROW_ON_ERROR),
                'created_at' => $occurredAt,
            ]);

            $award = ExperiencePointAward::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
            if ($inserted === 0) {
                return $award;
            }

            DB::table('learner_daily_engagements')->insertOrIgnore([
                'user_id' => $userId,
                'activity_date' => $activityDate,
                'xp_earned' => 0,
                'goal_xp' => $settings->daily_goal_xp,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
            $daily = LearnerDailyEngagement::query()->where('user_id', $userId)
                ->whereDate('activity_date', $activityDate)->lockForUpdate()->firstOrFail();
            $earned = $daily->xp_earned + $points;
            $daily->forceFill([
                'xp_earned' => $earned,
                'goal_completed_at' => $earned >= $daily->goal_xp ? ($daily->goal_completed_at ?? $occurredAt) : $daily->goal_completed_at,
            ])->save();

            $this->evaluateAchievements($user, $sourceType, $sourceId);

            return $award;
        });
    }

    private function evaluateAchievements(User $user, string $sourceType, ?int $sourceId): void
    {
        $lessonCount = LessonProgress::query()->where('user_id', $user->id)->where('status', 'completed')->count();
        $practiceCount = ExperiencePointAward::query()->where('user_id', $user->id)->where('source_type', 'adaptive_practice')->count();
        $vocabularyCount = ExperiencePointAward::query()->where('user_id', $user->id)->where('source_type', 'vocabulary_review')->count();
        $xp = (int) ExperiencePointAward::query()->where('user_id', $user->id)->sum('points');
        $settings = $this->settings($user);
        $streak = $this->currentStreak($user, $settings->timezone);
        $assessedSkills = collect();
        $masteredSkills = collect();
        if ($sourceType === 'adaptive_practice') {
            /** @var array<int, array{name: string, mastery_percentage: float|null}> $skillRows */
            $skillRows = app(LearnerProgressService::class)->summary($user)['skills'];
            $skills = collect($skillRows);
            $assessedSkills = $skills->filter(fn (array $skill): bool => $skill['mastery_percentage'] !== null);
            $masteredSkills = $assessedSkills->filter(fn (array $skill): bool => (float) $skill['mastery_percentage'] >= 80);
        }

        $checks = [
            'first_lesson' => [$lessonCount >= 1, ['completed_lessons' => $lessonCount]],
            'lessons_10' => [$lessonCount >= 10, ['completed_lessons' => $lessonCount]],
            'first_practice' => [$practiceCount >= 1, ['practice_sessions' => $practiceCount]],
            'vocabulary_50' => [$vocabularyCount >= 50, ['review_awards' => $vocabularyCount]],
            'streak_3' => [$streak >= 3, ['current_streak' => $streak]],
            'streak_7' => [$streak >= 7, ['current_streak' => $streak]],
            'first_skill_mastery' => [$assessedSkills->isNotEmpty(), ['assessed_skills' => $assessedSkills->pluck('name')->values()->all()]],
            'three_skills_mastered' => [$masteredSkills->count() >= 3, ['mastered_skills' => $masteredSkills->pluck('name')->values()->all()]],
            'xp_100' => [$xp >= 100, ['xp_total' => $xp]],
            'xp_500' => [$xp >= 500, ['xp_total' => $xp]],
        ];

        foreach ($checks as $key => [$qualified, $evidence]) {
            $definition = config('engagement.achievements.'.$key);
            if (! $qualified || ! is_array($definition) || LearnerAchievement::query()->where('user_id', $user->id)->where('achievement_key', $key)->exists()) {
                continue;
            }
            LearnerAchievement::query()->create([
                'user_id' => $user->id,
                'achievement_key' => $key,
                'title' => $definition['title'],
                'description' => $definition['description'],
                'evidence' => $evidence + ['source_type' => $sourceType, 'source_id' => $sourceId],
                'awarded_at' => now(),
            ]);
        }
    }

    private function currentStreak(User $user, string $timezone): int
    {
        $dates = LearnerDailyEngagement::query()->where('user_id', $user->id)
            ->whereNotNull('goal_completed_at')->whereDate('activity_date', '<=', CarbonImmutable::now($timezone)->toDateString())
            ->orderByDesc('activity_date')->pluck('activity_date');

        if ($dates->isEmpty()) {
            return 0;
        }

        $today = CarbonImmutable::now($timezone)->startOfDay();
        $last = CarbonImmutable::parse($dates->first(), $timezone)->startOfDay();
        if ($last->lt($today->subDay())) {
            return 0;
        }

        $expected = $last;
        $streak = 0;
        foreach ($dates as $date) {
            $day = CarbonImmutable::parse($date, $timezone)->startOfDay();
            if (! $day->equalTo($expected)) {
                break;
            }
            $streak++;
            $expected = $expected->subDay();
        }

        return $streak;
    }
}
