<?php

namespace Tests\Feature;

use App\Livewire\Resources\ExerciseRunner;
use App\Livewire\Tests\TakeTest;
use App\Models\AssessmentResult;
use App\Models\ClassGroup;
use App\Models\LearningAttempt;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\ReadingResource;
use App\Models\Resource;
use App\Models\Test as Assessment;
use App\Models\TestAttempt;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\User;
use App\Services\Learning\LearningAttemptService;
use App\Services\Test\TestAttemptService;
use App\Services\Test\TestScoringService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssessmentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_test_scoring_includes_unanswered_weighted_questions_in_every_denominator(): void
    {
        [$test, $questions, $topics] = $this->makeAssessment([
            ['section' => 'Grammar', 'points' => 2, 'correct' => true, 'topic' => 'Articles'],
            ['section' => 'Grammar', 'points' => 3, 'correct' => true, 'topic' => 'Articles'],
            ['section' => 'Vocabulary', 'points' => 5, 'correct' => false, 'topic' => 'Food'],
        ]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'weighted-assessment-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $correct = $questions[0]->options->firstWhere('is_correct', true);
        $incorrect = $questions[2]->options->firstWhere('is_correct', false);
        $service = app(TestAttemptService::class);
        $this->assertTrue($service->recordAnswer($attempt, $questions[0]->id, $correct->id, null, 'weighted-assessment-session'));
        $this->assertTrue($service->recordAnswer($attempt, $questions[2]->id, $incorrect->id, null, 'weighted-assessment-session'));
        $service->complete($attempt, null, 'weighted-assessment-session');

        $result = $attempt->fresh(['result']);
        $this->assertSame(2.0, (float) $result->score);
        $this->assertSame(10.0, (float) $result->total_points);
        $this->assertSame(20.0, (float) $result->percentage);
        $this->assertFalse((bool) $result->passed);
        $this->assertSame(5.0, (float) $result->result->breakdown['sections']['Grammar']['total_points']);
        $this->assertSame(40.0, (float) $result->result->breakdown['sections']['Grammar']['percentage']);
        $this->assertSame(5.0, (float) $result->result->breakdown['sections']['Vocabulary']['total_points']);
        $this->assertSame(0.0, (float) $result->result->breakdown['sections']['Vocabulary']['percentage']);
        $this->assertSame(5.0, (float) $result->result->breakdown['topics'][$topics['Articles']->id]['total_points']);
        $this->assertSame(40.0, (float) $result->result->breakdown['topics'][$topics['Articles']->id]['percentage']);
        $this->assertSame(5.0, (float) $result->result->breakdown['topics'][$topics['Food']->id]['total_points']);
        $this->withSession(['anon_session' => 'weighted-assessment-session'])
            ->get(route('tests.result', [$test, $attempt]))
            ->assertOk()
            ->assertSee('Fără răspuns');
    }

    public function test_empty_assessment_scores_zero_and_cannot_pass_even_with_zero_threshold(): void
    {
        $test = Assessment::factory()->create(['passing_score' => 0]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'empty-assessment-session', 'started_at' => now(), 'status' => 'started',
        ]);
        app(TestAttemptService::class)->complete($attempt, null, 'empty-assessment-session');

        $this->assertSame(0.0, (float) $attempt->fresh()->score);
        $this->assertSame(0.0, (float) $attempt->fresh()->total_points);
        $this->assertSame(0.0, (float) $attempt->fresh()->percentage);
        $this->assertFalse((bool) $attempt->fresh()->passed);
    }

    public function test_test_question_order_is_shuffled_once_and_persisted_for_the_attempt(): void
    {
        [$test] = $this->makeAssessment(array_fill(0, 8, ['section' => 'Grammar', 'points' => 1, 'correct' => true]), ['shuffle_questions' => true]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'shuffle-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $service = app(TestAttemptService::class);
        $first = array_column($service->questionPayload($attempt), 'id');
        $second = array_column($service->questionPayload($attempt->fresh()), 'id');

        $this->assertCount(8, $first);
        $this->assertSame($first, $second);
        $this->assertSame($first, $attempt->fresh()->question_order);
    }

    public function test_attempt_rejects_answers_from_another_test_and_options_from_another_question(): void
    {
        [$firstTest, $firstQuestions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]]);
        [, $secondQuestions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]]);
        $attempt = app(TestAttemptService::class)->start($firstTest, [
            'session_id' => 'membership-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $service = app(TestAttemptService::class);

        $this->assertFalse($service->recordAnswer($attempt, $secondQuestions[0]->id, $secondQuestions[0]->options->first()->id, null, 'membership-session'));
        $this->assertFalse($service->recordAnswer($attempt, $firstQuestions[0]->id, $secondQuestions[0]->options->first()->id, null, 'membership-session'));
        $this->assertDatabaseCount('test_answers', 0);
    }

    public function test_test_attempt_expires_on_the_server_and_saved_answers_are_scored_once(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]], ['duration_minutes' => 10]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'expired-session', 'started_at' => now()->subMinutes(9), 'status' => 'started',
        ]);
        $service = app(TestAttemptService::class);
        $this->assertTrue($service->recordAnswer(
            $attempt, $questions[0]->id, $questions[0]->options->firstWhere('is_correct', true)->id, null, 'expired-session',
        ));
        $this->travel(2)->minutes();
        $accepted = $service->recordAnswer(
            $attempt, $questions[0]->id, $questions[0]->options->firstWhere('is_correct', false)->id, null, 'expired-session',
        );
        $this->assertFalse($accepted);
        $this->assertSame('expired', $attempt->fresh()->status);

        $service->complete($attempt->fresh(), null, 'expired-session');
        $firstResultId = $attempt->fresh()->result->id;
        app(TestAttemptService::class)->complete($attempt->fresh(), null, 'expired-session');

        $this->assertSame('expired', $attempt->fresh()->status);
        $this->assertSame(1.0, (float) $attempt->fresh()->score);
        $this->assertSame($firstResultId, $attempt->fresh()->result->id);
        $this->assertDatabaseCount('assessment_results', 1);
        $this->withSession(['anon_session' => 'expired-session'])
            ->get(route('tests.certificate', [$test, $attempt]))
            ->assertNotFound();
        $this->get(route('tests.result', [$test, $attempt]))
            ->assertOk()
            ->assertSee('Timpul a expirat')
            ->assertDontSee('Răspuns corect:');
        $this->travelBack();
    }

    public function test_repeated_test_completion_is_idempotent(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 2, 'correct' => true]]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'idempotent-session', 'started_at' => now(), 'status' => 'started',
        ]);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'idempotent-session');
        $service = app(TestAttemptService::class);
        $service->complete($attempt, null, 'idempotent-session');
        $first = $attempt->fresh()->result;
        $service->complete($attempt->fresh(), null, 'idempotent-session');
        $this->assertFalse($service->recordAnswer(
            $attempt->fresh(), $questions[0]->id, $questions[0]->options->firstWhere('is_correct', false)->id, null, 'idempotent-session',
        ));

        $this->assertSame($first->id, $attempt->fresh()->result->id);
        $this->assertDatabaseCount('assessment_results', 1);
    }

    public function test_abandoned_test_attempts_cannot_be_opened_or_modified(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]]);
        $attempt = TestAttempt::query()->create([
            'test_id' => $test->id, 'session_id' => 'abandoned-attempt-session',
            'started_at' => now(), 'status' => 'abandoned',
        ]);
        $this->withSession(['anon_session' => 'abandoned-attempt-session'])
            ->get(route('tests.take', [$test, $attempt]))
            ->assertStatus(410);
        $this->assertFalse(app(TestAttemptService::class)->recordAnswer(
            $attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'abandoned-attempt-session',
        ));
        $this->assertDatabaseCount('test_answers', 0);
    }

    public function test_registered_and_premium_tests_enforce_access_rules(): void
    {
        $registered = Assessment::factory()->create(['access_type' => 'registered']);
        $premium = Assessment::factory()->create(['access_type' => 'premium']);
        $this->post(route('tests.start', $registered))->assertForbidden();
        $this->post(route('tests.start', $premium))->assertForbidden();
        $user = User::where('email', 'learner@engleza.wiki')->firstOrFail();
        $this->actingAs($user)->post(route('tests.start', $registered))->assertRedirect();
        $this->actingAs($user)->post(route('tests.start', $premium))->assertForbidden();
    }

    public function test_test_payload_and_result_respect_answer_disclosure_setting(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]], ['show_correct_answers' => false]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'disclosure-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $this->withSession(['anon_session' => 'disclosure-session']);
        $payload = app(TestAttemptService::class)->questionPayload($attempt);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'disclosure-session');
        $this->assertArrayNotHasKey('is_correct', $payload[0]['options'][0]);
        $this->assertArrayNotHasKey('explanation', $payload[0]);
        app(TestAttemptService::class)->complete($attempt, null, 'disclosure-session');
        $this->get(route('tests.result', [$test, $attempt]))->assertOk()->assertDontSee('Răspuns corect:')->assertDontSee('test-answer-review');

        $this->assertDatabaseHas('test_answers', ['test_attempt_id' => $attempt->id]);
    }

    public function test_configured_answer_disclosure_shows_correct_answers_only_after_completion(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]], ['show_correct_answers' => true]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'allowed-disclosure-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $payload = app(TestAttemptService::class)->questionPayload($attempt, revealAnswers: true);
        $this->assertArrayNotHasKey('is_correct', $payload[0]['options'][0]);
        $this->assertArrayNotHasKey('explanation', $payload[0]);

        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->firstWhere('is_correct', true)->id, null, 'allowed-disclosure-session');
        app(TestAttemptService::class)->complete($attempt, null, 'allowed-disclosure-session');
        $this->withSession(['anon_session' => 'allowed-disclosure-session'])
            ->get(route('tests.result', [$test, $attempt]))
            ->assertOk()
            ->assertSee('Răspuns corect:')
            ->assertSee('Explanation 0');
    }

    public function test_livewire_test_uses_server_validated_answers_and_persists_order(): void
    {
        [$test, $questions] = $this->makeAssessment([
            ['section' => 'Grammar', 'points' => 2, 'correct' => true],
            ['section' => 'Grammar', 'points' => 3, 'correct' => true],
        ]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'livewire-test-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $this->withSession(['anon_session' => 'livewire-test-session']);
        $component = Livewire::test(TakeTest::class, ['test' => $test, 'attempt' => $attempt]);
        $this->assertArrayNotHasKey('is_correct', $component->get('questions')[0]['options'][0]);
        $component->call('selectOption', $questions[0]->id, $questions[0]->options->first()->id)
            ->call('submit');

        $this->assertSame(2.0, (float) $attempt->fresh()->score);
        $this->assertSame(5.0, (float) $attempt->fresh()->total_points);
        $this->assertSame('completed', $attempt->fresh()->status);
        $this->assertDatabaseCount('assessment_results', 1);
    }

    public function test_interrupted_test_attempt_resumes_with_saved_answers_and_question_order(): void
    {
        [$test, $questions] = $this->makeAssessment([
            ['section' => 'Grammar', 'points' => 1, 'correct' => true],
            ['section' => 'Grammar', 'points' => 1, 'correct' => true],
        ], ['shuffle_questions' => true]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'resume-test-session', 'started_at' => now(), 'status' => 'started',
        ]);
        $selected = $questions[0]->options->firstWhere('is_correct', true);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $selected->id, null, 'resume-test-session');
        $this->withSession(['anon_session' => 'resume-test-session']);

        $component = Livewire::test(TakeTest::class, ['test' => $test, 'attempt' => $attempt->fresh()]);

        $this->assertSame($selected->id, $component->get('answers')[$questions[0]->id]);
        $this->assertSame($attempt->question_order, array_column($component->get('questions'), 'id'));
    }

    public function test_learning_attempt_scoring_includes_unanswered_weighted_questions(): void
    {
        [$resource, $questions, $topic] = $this->makeReadingResource([
            ['points' => 2, 'correct' => true],
            ['points' => 3, 'correct' => true],
        ]);
        $attempt = app(LearningAttemptService::class)->start($resource, null, 'learning-score-session', null, null);
        $this->assertTrue(app(LearningAttemptService::class)->recordAnswer(
            $attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'learning-score-session',
        ));
        $completed = app(LearningAttemptService::class)->complete($attempt, null, 'learning-score-session');

        $this->assertSame(2.0, (float) $completed->score);
        $this->assertSame(5.0, (float) $completed->total_points);
        $this->assertSame(40.0, (float) $completed->percentage);
        $this->assertSame(5.0, (float) $completed->breakdown['topics'][$topic->id]['total_points']);
        $this->assertSame(40.0, (float) $completed->breakdown['topics'][$topic->id]['percentage']);
        $this->withSession(['anon_session' => 'learning-score-session'])
            ->get(route('resources.result', [$resource, $attempt]))
            ->assertOk()
            ->assertSee('Fără răspuns');
    }

    public function test_learning_attempt_rejects_questions_and_options_from_another_resource(): void
    {
        [$resource, $questions] = $this->makeReadingResource([['points' => 1, 'correct' => true]]);
        [, $foreignQuestions] = $this->makeReadingResource([['points' => 1, 'correct' => true]]);
        $attempt = app(LearningAttemptService::class)->start($resource, null, 'learning-membership-session', null, null);
        $service = app(LearningAttemptService::class);

        $this->assertFalse($service->recordAnswer(
            $attempt, $foreignQuestions[0]->id, $foreignQuestions[0]->options->first()->id, null, 'learning-membership-session',
        ));
        $this->assertFalse($service->recordAnswer(
            $attempt, $questions[0]->id, $foreignQuestions[0]->options->first()->id, null, 'learning-membership-session',
        ));
        $this->assertDatabaseCount('learning_answers', 0);
    }

    public function test_learning_attempt_question_set_is_snapshotted_and_new_questions_are_rejected(): void
    {
        [$resource, $questions] = $this->makeReadingResource([['points' => 1, 'correct' => true]]);
        $attempt = app(LearningAttemptService::class)->start($resource, null, 'learning-snapshot-session', null, null);
        $reading = ReadingResource::query()->where('resource_id', $resource->id)->firstOrFail();
        $newQuestion = Question::query()->create([
            'reading_resource_id' => $reading->id, 'type' => 'single_choice',
            'question' => 'Added after start', 'points' => 10, 'sort_order' => 10,
        ]);
        $newOption = QuestionOption::query()->create([
            'question_id' => $newQuestion->id, 'label' => 'A', 'value' => 'New correct option',
            'is_correct' => true, 'sort_order' => 0,
        ]);

        $this->assertFalse(app(LearningAttemptService::class)->recordAnswer(
            $attempt, $newQuestion->id, $newOption->id, null, 'learning-snapshot-session',
        ));
        $this->assertTrue(app(LearningAttemptService::class)->recordAnswer(
            $attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'learning-snapshot-session',
        ));
        $completed = app(LearningAttemptService::class)->complete($attempt, null, 'learning-snapshot-session');

        $this->assertSame([$questions[0]->id], $completed->question_order);
        $this->assertSame(1.0, (float) $completed->total_points);
        $this->assertSame(100.0, (float) $completed->percentage);
    }

    public function test_passing_threshold_uses_the_rounded_percentage(): void
    {
        [$test, $questions] = $this->makeAssessment([
            ['section' => 'Grammar', 'points' => 6696, 'correct' => true],
            ['section' => 'Grammar', 'points' => 3304, 'correct' => false],
        ], ['passing_score' => 67]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'rounded-passing-session', 'started_at' => now(), 'status' => 'started',
        ]);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'rounded-passing-session');
        app(TestAttemptService::class)->complete($attempt, null, 'rounded-passing-session');

        $this->assertSame(67.0, (float) $attempt->fresh()->percentage);
        $this->assertTrue((bool) $attempt->fresh()->passed);
    }

    public function test_http_learning_controller_uses_the_shared_attempt_service(): void
    {
        [$resource, $questions] = $this->makeReadingResource([['points' => 2, 'correct' => true]]);
        $this->post(route('resources.attempt.start', $resource))->assertRedirect(route('resources.show', $resource));
        $attemptId = session('active_learning_attempts.'.$resource->id);
        $this->assertNotNull($attemptId);

        $this->post(route('resources.attempt.submit', $resource), [
            'attempt_id' => $attemptId,
            'answers' => [$questions[0]->id => $questions[0]->options->first()->id],
        ])->assertRedirect(route('resources.result', [$resource, $attemptId]));

        $attempt = LearningAttempt::query()->whereKey($attemptId)->firstOrFail();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(2.0, (float) $attempt->score);
        $this->assertNull(session('active_learning_attempts.'.$resource->id));
        $this->get(route('resources.result', [$resource, $attemptId]))->assertOk()->assertSee('Răspuns corect:');
    }

    public function test_livewire_learning_exercise_does_not_expose_answers_before_submission_and_is_idempotent(): void
    {
        [$resource, $questions] = $this->makeReadingResource([['points' => 1, 'correct' => true]]);
        $this->withSession(['anon_session' => 'learning-livewire-session']);
        $component = Livewire::test(ExerciseRunner::class, ['resource' => $resource]);
        $this->assertArrayNotHasKey('is_correct', $component->get('questions')[0]['options'][0]);
        $component->call('pick', $questions[0]->id, $questions[0]->options->first()->id)
            ->call('submit')
            ->call('submit');

        $attempt = $component->get('attempt');
        $freshAttempt = LearningAttempt::query()->findOrFail($attempt->id);
        $this->assertSame('completed', $freshAttempt->status);
        $this->assertSame(100.0, (float) $freshAttempt->percentage);
        $this->assertDatabaseCount('learning_attempts', 1);
        $this->assertDatabaseCount('learning_answers', 1);
        $this->assertArrayHasKey('is_correct', $component->get('questions')[0]['options'][0]);
    }

    public function test_learning_resource_answer_disclosure_setting_is_enforced_after_completion(): void
    {
        [$resource, $questions] = $this->makeReadingResource([['points' => 1, 'correct' => true]], false);
        $unattemptedPayload = app(LearningAttemptService::class)->questionPayload($resource, revealAnswers: true);
        $this->assertArrayNotHasKey('is_correct', $unattemptedPayload[0]['options'][0]);
        $this->assertArrayNotHasKey('explanation', $unattemptedPayload[0]);
        $this->withSession(['anon_session' => 'learning-no-disclosure-session']);
        $component = Livewire::test(ExerciseRunner::class, ['resource' => $resource]);
        $component->call('pick', $questions[0]->id, $questions[0]->options->first()->id)->call('submit');

        $this->assertArrayNotHasKey('is_correct', $component->get('questions')[0]['options'][0]);
        $attempt = $component->get('attempt');
        $this->get(route('resources.result', [$resource, $attempt->id]))
            ->assertOk()
            ->assertDontSee('Răspuns corect:')
            ->assertDontSee('Reading explanation')
            ->assertDontSee('Incorrect')
            ->assertSee('Trimis');
    }

    public function test_guest_attempt_results_and_certificates_are_private_to_the_originating_session(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 1, 'correct' => true]]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'private-result-session', 'started_at' => now(), 'status' => 'started',
        ]);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->first()->id, null, 'private-result-session');
        app(TestAttemptService::class)->complete($attempt, null, 'private-result-session');

        $this->withSession(['anon_session' => 'another-session'])->get(route('tests.result', [$test, $attempt]))->assertForbidden();
        $this->get(route('tests.certificate', [$test, $attempt]))->assertForbidden();
        $this->withSession(['anon_session' => 'private-result-session'])->get(route('tests.result', [$test, $attempt]))->assertOk();
        $this->get(route('tests.certificate', [$test, $attempt]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_cefr_estimation_without_a_test_level_preserves_the_existing_thresholds(): void
    {
        [$test, $questions] = $this->makeAssessment([
            ['section' => 'Grammar', 'points' => 9, 'correct' => true],
            ['section' => 'Grammar', 'points' => 1, 'correct' => false],
        ], ['level_id' => null]);
        $attempt = app(TestAttemptService::class)->start($test, [
            'session_id' => 'cefr-default-session', 'started_at' => now(), 'status' => 'started',
        ]);
        app(TestAttemptService::class)->recordAnswer($attempt, $questions[0]->id, $questions[0]->options->firstWhere('is_correct', true)->id, null, 'cefr-default-session');
        app(TestAttemptService::class)->complete($attempt, null, 'cefr-default-session');

        $this->assertSame('C1', $attempt->fresh()->level_result);
    }

    public function test_historical_completed_attempts_keep_existing_scores_without_recalculating_changed_content(): void
    {
        [$test, $questions] = $this->makeAssessment([['section' => 'Grammar', 'points' => 10, 'correct' => true]]);
        $testAttempt = TestAttempt::query()->create([
            'test_id' => $test->id, 'session_id' => 'legacy-test-history', 'started_at' => now()->subDay(),
            'completed_at' => now()->subDay(), 'status' => 'completed', 'score' => 7, 'percentage' => 70,
        ]);
        $result = AssessmentResult::query()->create([
            'test_attempt_id' => $testAttempt->id, 'overall_score' => 7, 'overall_percentage' => 70, 'level' => 'A2',
        ]);
        $this->assertSame($result->id, app(TestScoringService::class)->score($testAttempt)->id);
        $this->assertSame([], app(TestAttemptService::class)->questionPayload($testAttempt->fresh(), revealAnswers: true));
        $this->assertSame(7.0, (float) $testAttempt->fresh()->score);

        [$resource] = $this->makeReadingResource([['points' => 10, 'correct' => true]]);
        $learningAttempt = LearningAttempt::query()->create([
            'resource_id' => $resource->id, 'session_id' => 'legacy-learning-history',
            'started_at' => now()->subDay(), 'completed_at' => now()->subDay(),
            'status' => 'completed', 'score' => 6, 'percentage' => 60,
        ]);
        $completed = app(LearningAttemptService::class)->complete($learningAttempt, null, 'legacy-learning-history');

        $this->assertSame(6.0, (float) $completed->score);
        $this->assertNull($completed->question_order);
        $this->assertSame([], app(LearningAttemptService::class)->questionPayload($resource, revealAnswers: true, attempt: $completed));
    }

    public function test_class_group_results_are_limited_to_teacher_and_admin(): void
    {
        $teacher = User::where('email', 'teacher@engleza.wiki')->firstOrFail();
        $learner = User::where('email', 'learner@engleza.wiki')->firstOrFail();
        $group = ClassGroup::create([
            'name' => 'Integrity class', 'join_code' => 'INTEGRITY1', 'teacher_id' => $teacher->id,
            'is_active' => true,
        ]);
        $this->actingAs($learner)->get(route('classes.results', $group->join_code))->assertForbidden();
        $this->actingAs($teacher)->get(route('classes.results', $group->join_code))->assertOk();
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('classes.results', $group->join_code))->assertOk();
    }

    /**
     * @param  array<int, array{section: string, points: int, correct: bool, topic?: string}>  $definitions
     * @param  array<string, mixed>  $testAttributes
     * @return array{0: Assessment, 1: array<int, Question>, 2: array<string, Topic>}
     */
    public function test_all_supported_question_types_run_through_both_assessment_and_learning_components(): void
    {
        $user = User::factory()->create();
        $cases = [
            'single_choice' => ['response' => 'option'],
            'true_false' => ['response' => 'option'],
            'multiple_choice' => ['response' => 'multiple'],
            'text' => ['response' => 'RIGHT ANSWER'],
            'short_text' => ['response' => 'right answer'],
            'matching' => ['response' => ['cat' => 'gato', 'dog' => 'perro'], 'metadata' => ['pairs' => [
                ['left' => 'cat', 'right' => 'gato'], ['left' => 'dog', 'right' => 'perro'],
            ]]],
            'ordering' => ['response' => [0 => 'first', 1 => 'second'], 'metadata' => ['items' => ['first', 'second']]],
            'gap_fill' => ['response' => ['gap_1' => 'is'], 'metadata' => ['gaps' => ['gap_1' => 'is']]],
        ];

        foreach ($cases as $type => $case) {
            [$test, $testQuestions] = $this->makeAssessment([['section' => 'Types', 'points' => 4, 'correct' => true]]);
            [$resource, $learningQuestions] = $this->makeReadingResource([['points' => 4, 'correct' => true]]);
            $testQuestion = $testQuestions[0];
            $learningQuestion = $learningQuestions[0];
            foreach ([$testQuestion, $learningQuestion] as $question) {
                $options = $question->options;
                $metadata = $case['metadata'] ?? [];
                if ($type === 'multiple_choice') {
                    $options[1]->update(['is_correct' => true]);
                }
                if (in_array($type, ['text', 'short_text'], true)) {
                    $options[0]->update(['value' => 'Right answer']);
                }
                $question->update(['type' => $type, 'metadata' => $metadata]);
            }
            $testResponse = match ($case['response']) {
                'option' => (int) $testQuestion->options[0]->id,
                'multiple' => [(int) $testQuestion->options[0]->id, (int) $testQuestion->options[1]->id],
                default => $case['response'],
            };
            $learningResponse = match ($case['response']) {
                'option' => (int) $learningQuestion->options[0]->id,
                'multiple' => [(int) $learningQuestion->options[0]->id, (int) $learningQuestion->options[1]->id],
                default => $case['response'],
            };

            $testAttempt = app(TestAttemptService::class)->start($test, [
                'user_id' => $user->id, 'started_at' => now(), 'status' => 'started',
            ]);
            $beforeTest = app(TestAttemptService::class)->questionPayload($testAttempt);
            self::assertArrayNotHasKey('answer_key', $beforeTest[0], $type);
            self::assertArrayNotHasKey('is_correct', $beforeTest[0]['options'][0] ?? [], $type);
            Livewire::actingAs($user)->test(TakeTest::class, ['test' => $test, 'attempt' => $testAttempt])
                ->call('setAnswer', (int) $testQuestion->id, $testResponse)->call('submit');
            $testAttempt->refresh();
            self::assertSame('completed', $testAttempt->status, $type.' test');
            self::assertSame(4.0, (float) $testAttempt->score, $type.' test score');
            self::assertArrayHasKey('answer_key', app(TestAttemptService::class)->questionPayload($testAttempt, true)[0], $type.' test answer key');

            $beforeLearning = app(LearningAttemptService::class)->questionPayload($resource);
            self::assertArrayNotHasKey('answer_key', $beforeLearning[0], $type);
            self::assertArrayNotHasKey('is_correct', $beforeLearning[0]['options'][0] ?? [], $type);
            $component = Livewire::actingAs($user)->test(ExerciseRunner::class, ['resource' => $resource]);
            $component->call('setAnswer', (int) $learningQuestion->id, $learningResponse)->call('submit');
            $learningAttempt = $component->get('attempt');
            self::assertSame('completed', $learningAttempt->status, $type.' learning');
            self::assertSame(4.0, (float) $learningAttempt->score, $type.' learning score');
            self::assertArrayHasKey('answer_key', $component->get('questions')[0], $type.' learning answer key');
        }
    }

    private function makeAssessment(array $definitions, array $testAttributes = []): array
    {
        $test = Assessment::query()->create(Assessment::factory()->raw($testAttributes));
        $sections = [];
        $questions = [];
        $topics = [];
        foreach ($definitions as $index => $definition) {
            $sectionName = $definition['section'];
            $sections[$sectionName] ??= TestSection::query()->create([
                'test_id' => $test->id, 'title' => $sectionName, 'sort_order' => count($sections),
            ]);
            $question = Question::query()->create([
                'test_section_id' => $sections[$sectionName]->id, 'type' => 'single_choice',
                'question' => 'Question '.$index, 'explanation' => 'Explanation '.$index,
                'points' => $definition['points'], 'sort_order' => $index,
            ]);
            $correct = QuestionOption::query()->create([
                'question_id' => $question->id, 'label' => 'A', 'value' => 'Correct '.$index,
                'is_correct' => true, 'sort_order' => 0,
            ]);
            QuestionOption::query()->create([
                'question_id' => $question->id, 'label' => 'B', 'value' => 'Incorrect '.$index,
                'is_correct' => false, 'sort_order' => 1,
            ]);
            if (isset($definition['topic'])) {
                $name = $definition['topic'];
                $topics[$name] ??= Topic::query()->firstOrCreate(['slug' => 'assessment-'.strtolower($name)], ['name' => $name]);
                $question->topics()->sync([$topics[$name]->id]);
            }
            $questions[] = $question->load('options');
        }

        return [$test, $questions, $topics];
    }

    /**
     * @param  array<int, array{points: int, correct: bool}>  $definitions
     * @return array{0: \App\Models\Resource, 1: array<int, Question>, 2: Topic}
     */
    private function makeReadingResource(array $definitions, bool $showCorrectAnswers = true): array
    {
        $resource = Resource::query()->create(Resource::factory()->raw(['type' => 'reading']));
        $reading = ReadingResource::query()->create([
            'resource_id' => $resource->id, 'title' => 'Integrity reading',
            'instructions' => 'Read carefully', 'content' => 'A short reading passage.',
            'show_correct_answers' => $showCorrectAnswers,
        ]);
        $topic = Topic::query()->firstOrCreate(['slug' => 'assessment-reading-topic'], ['name' => 'Reading assessment topic']);
        $questions = [];
        foreach ($definitions as $index => $definition) {
            $question = Question::query()->create([
                'reading_resource_id' => $reading->id, 'type' => 'single_choice',
                'question' => 'Reading question '.$index, 'explanation' => 'Reading explanation '.$index,
                'points' => $definition['points'], 'sort_order' => $index,
            ]);
            QuestionOption::query()->create([
                'question_id' => $question->id, 'label' => 'A', 'value' => 'Correct reading answer',
                'is_correct' => true, 'sort_order' => 0,
            ]);
            QuestionOption::query()->create([
                'question_id' => $question->id, 'label' => 'B', 'value' => 'Incorrect reading answer',
                'is_correct' => false, 'sort_order' => 1,
            ]);
            $question->topics()->sync([$topic->id]);
            $questions[] = $question->load('options');
        }

        return [$resource, $questions, $topic];
    }
}
