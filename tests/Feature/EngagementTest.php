<?php

namespace Tests\Feature;

use App\Models\AdaptivePracticeSession;
use App\Models\ExperiencePointAward;
use App\Models\LearnerAchievement;
use App\Models\LearnerDailyEngagement;
use App\Models\User;
use App\Models\VocabularyReviewEvent;
use App\Services\Learning\EngagementService;
use App\Services\Learning\LearnerProgressService;
use Carbon\Carbon;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EngagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_learning_activity_awards_idempotent_xp_and_updates_the_daily_goal(): void
    {
        $user = User::factory()->create();
        $engagement = app(EngagementService::class);

        $first = $engagement->awardLessonCompletion($user->id, 41);
        $retry = $engagement->awardLessonCompletion($user->id, 41);

        self::assertNotNull($first);
        self::assertSame($first->id, $retry?->id);
        $this->assertDatabaseCount('experience_point_awards', 1);
        $this->assertDatabaseHas('experience_point_awards', [
            'user_id' => $user->id,
            'source_type' => 'lesson',
            'source_id' => 41,
            'points' => 20,
            'idempotency_key' => 'lesson:'.$user->id.':41',
        ]);
        $this->assertDatabaseHas('learner_daily_engagements', [
            'user_id' => $user->id,
            'xp_earned' => 20,
            'goal_xp' => 20,
        ]);

        $summary = $engagement->summary($user);
        self::assertSame(20, $summary['xp_total']);
        self::assertTrue($summary['daily_goal']['completed']);
        self::assertSame(1, $summary['current_streak']);
    }

    public function test_daily_goal_settings_are_validated_and_timezone_is_used_across_dst(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertDatabaseCount('learner_daily_engagements', 0);
        $this->actingAs($user)->put(route('engagement.goal.update'), [
            'daily_goal_xp' => 10,
            'timezone' => 'Europe/Bucharest',
        ])->assertRedirect(route('dashboard'));

        $engagement = app(EngagementService::class);
        Carbon::setTestNow(Carbon::parse('2026-03-28 23:30:00', 'UTC'));
        $engagement->awardLessonCompletion($user->id, 101);
        Carbon::setTestNow(Carbon::parse('2026-03-29 22:30:00', 'UTC'));
        $engagement->awardLessonCompletion($user->id, 102);
        Carbon::setTestNow(Carbon::parse('2026-03-30 22:30:00', 'UTC'));
        $engagement->awardLessonCompletion($user->id, 103);

        $summary = $engagement->summary($user);
        self::assertSame('Europe/Bucharest', $summary['timezone']);
        self::assertSame(3, $summary['current_streak']);
        self::assertSame(60, $summary['xp_total']);
        $this->assertDatabaseHas('learner_daily_engagements', [
            'user_id' => $user->id,
            'activity_date' => '2026-03-29',
        ]);
        $this->assertDatabaseHas('learner_daily_engagements', [
            'user_id' => $user->id,
            'activity_date' => '2026-03-30',
        ]);
        $this->assertDatabaseHas('learner_daily_engagements', [
            'user_id' => $user->id,
            'activity_date' => '2026-03-31',
        ]);
        $this->assertDatabaseHas('learner_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'streak_3',
        ]);
        Carbon::setTestNow();
    }

    public function test_adaptive_practice_xp_is_idempotent_and_capped_per_day(): void
    {
        $user = User::factory()->create();
        $engagement = app(EngagementService::class);
        $sessions = [];
        for ($i = 0; $i < 4; $i++) {
            $sessions[] = AdaptivePracticeSession::query()->create([
                'user_id' => $user->id,
                'question_order' => [],
                'status' => 'completed',
                'difficulty_min' => 'easy',
                'difficulty_max' => 'medium',
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        }

        $engagement->awardPracticeCompletion($user, $sessions[0]);
        $engagement->awardPracticeCompletion($user, $sessions[0]);
        $engagement->awardPracticeCompletion($user, $sessions[1]);
        $engagement->awardPracticeCompletion($user, $sessions[2]);
        $capped = $engagement->awardPracticeCompletion($user, $sessions[3]);

        self::assertNull($capped);
        $this->assertDatabaseCount('experience_point_awards', 3);
        self::assertSame(30, (int) ExperiencePointAward::query()->where('user_id', $user->id)->sum('points'));
        $this->assertDatabaseHas('learner_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'first_practice',
        ]);
    }

    public function test_vocabulary_review_xp_is_limited_to_one_award_per_item_per_day_and_ten_per_day(): void
    {
        $user = User::factory()->create();
        $engagement = app(EngagementService::class);

        for ($i = 1; $i <= 11; $i++) {
            $event = new VocabularyReviewEvent([
                'id' => $i,
                'learner_vocabulary_item_id' => $i,
                'user_id' => $user->id,
                'grade' => 'good',
                'reviewed_at' => now(),
            ]);
            $engagement->awardVocabularyReview($user, $event);
            if ($i === 1) {
                $engagement->awardVocabularyReview($user, $event);
            }
        }

        $this->assertDatabaseCount('experience_point_awards', 10);
        self::assertSame(20, (int) ExperiencePointAward::query()->where('user_id', $user->id)->sum('points'));
        self::assertSame(20, LearnerDailyEngagement::query()->where('user_id', $user->id)->value('xp_earned'));
    }

    public function test_xp_milestone_achievement_is_awarded_once_and_dashboard_is_private(): void
    {
        $user = User::factory()->create();
        $engagement = app(EngagementService::class);
        for ($lessonId = 1; $lessonId <= 5; $lessonId++) {
            $engagement->awardLessonCompletion($user->id, $lessonId);
        }

        $this->assertDatabaseHas('learner_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'xp_100',
        ]);
        self::assertSame(1, LearnerAchievement::query()->where('user_id', $user->id)->where('achievement_key', 'xp_100')->count());

        $otherUser = User::factory()->create();
        $otherAward = $engagement->awardLessonCompletion($otherUser->id, 1);
        self::assertNotNull($otherAward);
        self::assertSame(20, $engagement->summary($otherUser)['xp_total']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Progresul tău')
            ->assertSee('Primii pași')
            ->assertDontSee($otherUser->name);
    }

    public function test_skill_mastery_achievement_uses_the_existing_evidence_based_progress_profile(): void
    {
        $user = User::factory()->create();
        $session = AdaptivePracticeSession::query()->create([
            'user_id' => $user->id,
            'question_order' => [],
            'status' => 'completed',
            'difficulty_min' => 'easy',
            'difficulty_max' => 'medium',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->mock(LearnerProgressService::class)
            ->shouldReceive('summary')->once()->andReturn([
                'skills' => [
                    ['name' => 'Grammar', 'mastery_percentage' => 84.0],
                    ['name' => 'Vocabulary', 'mastery_percentage' => 82.0],
                    ['name' => 'Listening', 'mastery_percentage' => 91.0],
                ],
            ]);

        app(EngagementService::class)->awardPracticeCompletion($user, $session);

        $this->assertDatabaseHas('learner_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'first_skill_mastery',
        ]);
        $this->assertDatabaseHas('learner_achievements', [
            'user_id' => $user->id,
            'achievement_key' => 'three_skills_mastered',
        ]);
        $achievement = LearnerAchievement::query()->where('user_id', $user->id)->where('achievement_key', 'first_skill_mastery')->firstOrFail();
        self::assertSame('adaptive_practice', $achievement->evidence['source_type']);
        self::assertSame($session->id, $achievement->evidence['source_id']);
    }

    public function test_reward_issuance_locks_the_learner_settings_row_to_serialize_daily_limits(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('The row-lock assertion requires the MySQL CI database.');
        }

        $user = User::factory()->create();
        $lockQueries = [];
        DB::listen(function (QueryExecuted $query) use (&$lockQueries): void {
            if (str_contains(strtolower($query->sql), 'for update')) {
                $lockQueries[] = $query->sql;
            }
        });

        app(EngagementService::class)->awardLessonCompletion($user->id, 9001);

        self::assertNotEmpty($lockQueries);
        self::assertStringContainsString('learner_engagement_settings', strtolower(implode(' ', $lockQueries)));
    }

    public function test_daily_goal_update_rejects_invalid_goal_and_timezone(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('engagement.goal.update'), [
            'daily_goal_xp' => 15,
            'timezone' => 'Mars/Olympus',
        ])->assertSessionHasErrors(['daily_goal_xp', 'timezone']);
    }
}
