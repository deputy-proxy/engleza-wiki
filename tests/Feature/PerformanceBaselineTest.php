<?php

namespace Tests\Feature;

use App\Models\ClassGroup;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\User;
use App\Services\Learning\AdaptivePracticeService;
use App\Services\Learning\ClassGroupService;
use App\Services\Learning\LearnerProgressService;
use App\Services\Test\TestAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceBaselineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->approveSeededContent();
    }

    public function test_progress_summary_query_count_is_stable_at_10_100_and_1000_completed_attempts(): void
    {
        $learner = User::query()->where('role', 'learner')->firstOrFail();
        $assessment = Assessment::query()->where('slug', 'vocabulary-test-everyday-english')->firstOrFail();
        $questions = $assessment->allQuestions()->take(5)->values();
        $this->assertCount(5, $questions);
        $correctOptions = $questions->mapWithKeys(fn ($question) => [
            $question->id => $question->options()->where('is_correct', true)->firstOrFail()->id,
        ]);

        $connection = DB::connection();
        $connection->enableQueryLog();
        $measurements = [];
        $inserted = 0;

        foreach ([10, 100, 1000] as $target) {
            $attemptRows = [];
            $sessionIds = [];
            for ($index = $inserted; $index < $target; $index++) {
                $sessionId = 'performance-profile-'.$learner->id.'-'.$index;
                $sessionIds[] = $sessionId;
                $attemptRows[] = [
                    'test_id' => $assessment->id,
                    'user_id' => $learner->id,
                    'session_id' => $sessionId,
                    'started_at' => now()->subMinutes($index + 1),
                    'completed_at' => now()->subMinutes($index + 1),
                    'status' => 'completed',
                    'score' => 1,
                    'percentage' => 100,
                    'level_result' => 'A1',
                    'time_spent_seconds' => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            foreach (array_chunk($attemptRows, 500) as $batch) {
                DB::table('test_attempts')->insert($batch);
            }
            $attemptIds = DB::table('test_attempts')->whereIn('session_id', $sessionIds)->pluck('id', 'session_id');
            $answerRows = [];
            foreach ($sessionIds as $index => $sessionId) {
                $question = $questions[$index % $questions->count()];
                $answerRows[] = [
                    'test_attempt_id' => $attemptIds[$sessionId],
                    'question_id' => $question->id,
                    'question_option_id' => $correctOptions[$question->id],
                    'answer_text' => null,
                    'is_correct' => true,
                    'points_awarded' => 1,
                    'answered_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            foreach (array_chunk($answerRows, 500) as $batch) {
                DB::table('test_answers')->insert($batch);
            }

            $connection->flushQueryLog();
            memory_reset_peak_usage();
            $memoryBefore = memory_get_usage(false);
            $startedAt = hrtime(true);
            $profile = app(LearnerProgressService::class)->summary($learner);
            $queryLog = $connection->getQueryLog();
            $slowestQuery = collect($queryLog)->sortByDesc('time')->first();
            $measurements[] = [
                'attempts' => $target,
                'queries' => count($queryLog),
                'slowest_query_ms' => round((float) ($slowestQuery['time'] ?? 0), 2),
                'slowest_query' => substr((string) ($slowestQuery['query'] ?? ''), 0, 180),
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                'peak_memory_delta_bytes' => max(0, memory_get_peak_usage(false) - $memoryBefore),
                'completed_attempts' => $profile['stats']['completed_tests'],
            ];
            $connection->flushQueryLog();
            $inserted = $target;
        }

        foreach ($measurements as $measurement) {
            $this->assertSame($measurement['attempts'], $measurement['completed_attempts']);
            $this->assertLessThanOrEqual(20, $measurement['queries'], 'Progress query count exceeded its history-size budget: '.json_encode($measurement));
        }

        if (getenv('PERFORMANCE_PROFILE_OUTPUT') === '1') {
            fwrite(STDOUT, 'PROGRESS_SCALE_BASELINE '.json_encode($measurements, JSON_THROW_ON_ERROR).PHP_EOL);
        }
    }

    public function test_search_and_catalogue_keep_bounded_query_counts_at_increasing_resource_sizes(): void
    {
        $connection = DB::connection();
        $connection->enableQueryLog();
        $measure = function (string $name, callable $callback) use ($connection): array {
            $connection->flushQueryLog();
            memory_reset_peak_usage();
            $memoryBefore = memory_get_usage(false);
            $startedAt = hrtime(true);
            $callback();
            $queryLog = $connection->getQueryLog();
            $slowestQuery = collect($queryLog)->sortByDesc('time')->first();
            $connection->flushQueryLog();

            return [
                'flow' => $name,
                'queries' => count($queryLog),
                'slowest_query_ms' => round((float) ($slowestQuery['time'] ?? 0), 2),
                'slowest_query' => substr((string) ($slowestQuery['query'] ?? ''), 0, 180),
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                'peak_memory_delta_bytes' => max(0, memory_get_peak_usage(false) - $memoryBefore),
            ];
        };

        $measurements = [];
        foreach ([10, 100, 1000] as $size) {
            Resource::factory()->count($size)
                ->sequence(fn ($sequence) => [
                    'title' => 'Profile resource '.$size.' '.$sequence->index,
                    'slug' => 'profile-resource-'.$size.'-'.$sequence->index,
                ])
                ->create(['status' => 'published', 'published_at' => now()]);

            $measurements[] = $measure('search_'.$size, fn () => $this->get(route('search', ['q' => 'past']))->assertOk()->assertSee('past'));
            $measurements[] = $measure('resource_catalogue_'.$size, fn () => $this->get(route('resources.index'))->assertOk());
        }

        foreach ($measurements as $measurement) {
            $budget = str_starts_with($measurement['flow'], 'search_') ? 15 : 25;
            $this->assertLessThanOrEqual(
                $budget,
                $measurement['queries'],
                $measurement['flow'].' exceeded its query budget: '.json_encode($measurement),
            );
        }

        if (getenv('PERFORMANCE_PROFILE_OUTPUT') === '1') {
            fwrite(STDOUT, 'PERFORMANCE_SCALE_BASELINE '.json_encode($measurements, JSON_THROW_ON_ERROR).PHP_EOL);
        }
    }

    public function test_key_learner_and_content_routes_have_recorded_query_budgets(): void
    {
        $learner = User::query()->where('role', 'learner')->firstOrFail();
        $teacher = User::query()->where('role', 'teacher')->firstOrFail();
        $group = ClassGroup::query()->create(['name' => 'Performance profile class', 'teacher_id' => $teacher->id]);
        app(ClassGroupService::class)->join($learner, $group);
        $assessment = Assessment::query()->where('slug', 'vocabulary-test-everyday-english')->firstOrFail();
        $attempts = app(TestAttemptService::class);
        $attempt = $attempts->start($assessment, [
            'user_id' => $learner->id,
            'class_group_id' => $group->id,
            'started_at' => now(),
        ]);
        $question = $assessment->allQuestions()->firstOrFail();
        $correctOption = $question->options()->where('is_correct', true)->firstOrFail();
        $attempts->recordAnswer($attempt, $question->id, $correctOption->id, $learner->id, null);
        $attempts->complete($attempt, $learner->id, null);
        $session = app(AdaptivePracticeService::class)->start($learner);

        $connection = DB::connection();
        $connection->enableQueryLog();

        $measure = function (string $name, callable $callback) use ($connection): array {
            $connection->flushQueryLog();
            memory_reset_peak_usage();
            $memoryBefore = memory_get_usage(false);
            $startedAt = hrtime(true);
            $callback();
            $queryLog = $connection->getQueryLog();
            $slowestQuery = collect($queryLog)->sortByDesc('time')->first();
            $connection->flushQueryLog();

            return [
                'flow' => $name,
                'queries' => count($queryLog),
                'slowest_query_ms' => round((float) ($slowestQuery['time'] ?? 0), 2),
                'slowest_query' => substr((string) ($slowestQuery['query'] ?? ''), 0, 180),
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                'peak_memory_delta_bytes' => max(0, memory_get_peak_usage(false) - $memoryBefore),
            ];
        };

        $baseline = [
            $measure('search', fn () => $this->get(route('search', ['q' => 'past']))->assertOk()),
            $measure('resource_catalogue', fn () => $this->get(route('resources.index'))->assertOk()),
            $measure('dashboard', fn () => $this->actingAs($learner)->get(route('dashboard'))->assertOk()),
            $measure('vocabulary_reviews', fn () => $this->actingAs($learner)->get(route('reviews.index'))->assertOk()),
            $measure('adaptive_practice', fn () => $this->actingAs($learner)->get(route('practice.show', $session))->assertOk()),
            $measure('teacher_summary', fn () => app(LearnerProgressService::class)->classGroupSummary($group)),
        ];

        $budgets = [
            'search' => 15,
            'resource_catalogue' => 25,
            'dashboard' => 60,
            'vocabulary_reviews' => 10,
            'adaptive_practice' => 15,
            'teacher_summary' => 25,
        ];

        foreach ($baseline as $measurement) {
            $this->assertLessThanOrEqual(
                $budgets[$measurement['flow']],
                $measurement['queries'],
                $measurement['flow'].' exceeded its local seeded query budget: '.json_encode($measurement),
            );
        }

        if (getenv('PERFORMANCE_PROFILE_OUTPUT') === '1') {
            fwrite(STDOUT, 'PERFORMANCE_BASELINE '.json_encode($baseline, JSON_THROW_ON_ERROR).PHP_EOL);
        }
    }
}
