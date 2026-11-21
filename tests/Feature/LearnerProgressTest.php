<?php

namespace Tests\Feature;

use App\Models\AdaptivePracticeSession;
use App\Models\CardResource;
use App\Models\ClassGroup;
use App\Models\ExperiencePointAward;
use App\Models\LearnerVocabularyItem;
use App\Models\LearningAttempt;
use App\Models\LearningLesson;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\LearningUnit;
use App\Models\LessonActivity;
use App\Models\LessonProgress;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Resource;
use App\Models\ResourceCard;
use App\Models\Skill;
use App\Models\Test as Assessment;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\User;
use App\Services\Learning\AdaptivePracticeService;
use App\Services\Learning\ClassGroupService;
use App\Services\Learning\LearnerProgressService;
use App\Services\Learning\VocabularyReviewService;
use App\Services\Test\TestAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearnerProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_mastery_requires_distinct_question_evidence_across_multiple_attempts(): void
    {
        $user = User::factory()->create();
        $skill = Skill::query()->create(['name' => 'Grammar', 'slug' => 'grammar']);
        $topic = Topic::query()->create(['name' => 'Articles', 'slug' => 'articles']);
        $level = Level::query()->create(['name' => 'A1', 'slug' => 'a1']);
        $test = Assessment::factory()->create(['skill_id' => $skill->id, 'level_id' => $level->id]);
        $section = TestSection::query()->create(['test_id' => $test->id, 'title' => 'Grammar']);
        $questions = collect();

        for ($index = 1; $index <= 5; $index++) {
            $question = Question::query()->create([
                'test_section_id' => $section->id, 'type' => 'single_choice',
                'question' => 'Question '.$index, 'points' => 1,
                'difficulty' => $index === 5 ? 'hard' : 'easy', 'sort_order' => $index,
            ]);
            $correct = QuestionOption::query()->create([
                'question_id' => $question->id, 'value' => 'Correct '.$index,
                'label' => 'Correct '.$index, 'is_correct' => true, 'sort_order' => 1,
            ]);
            QuestionOption::query()->create([
                'question_id' => $question->id, 'value' => 'Wrong '.$index,
                'label' => 'Wrong '.$index, 'is_correct' => false, 'sort_order' => 2,
            ]);
            $question->topics()->attach($topic);
            $questions->push([$question, $correct]);
        }
        $test->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $attempts = app(TestAttemptService::class);
        $first = $attempts->start($test, [
            'user_id' => $user->id, 'started_at' => now(), 'status' => 'started',
        ]);
        foreach ($questions as [$question, $correct]) {
            $this->assertTrue($attempts->recordAnswer($first, $question->id, $correct->id, $user->id, null));
        }
        $attempts->complete($first, $user->id, null);

        $profile = app(LearnerProgressService::class)->summary($user);
        $topicProgress = collect($profile['topics'])->firstWhere('id', $topic->id);
        $this->assertSame('insufficient_evidence', $topicProgress['mastery_state']);
        $this->assertNull($topicProgress['mastery_percentage']);
        $this->assertSame(5, $topicProgress['unique_questions']);
        $this->assertSame(1, $topicProgress['attempt_count']);

        $second = $attempts->start($test, [
            'user_id' => $user->id, 'started_at' => now(), 'status' => 'started',
        ]);
        foreach ($questions as [$question, $correct]) {
            $this->assertTrue($attempts->recordAnswer($second, $question->id, $correct->id, $user->id, null));
        }
        $attempts->complete($second, $user->id, null);

        $profile = app(LearnerProgressService::class)->summary($user);
        $topicProgress = collect($profile['topics'])->firstWhere('id', $topic->id);
        $this->assertSame(100.0, $topicProgress['mastery_percentage']);
        $this->assertSame('strong', $topicProgress['mastery_state']);
        $this->assertSame('moderate', $topicProgress['confidence']);
        $skillProgress = collect($profile['skills'])->firstWhere('id', $skill->id);
        $this->assertSame(100.0, $skillProgress['mastery_percentage']);
        $this->assertSame(2, $topicProgress['attempt_count']);
        $this->assertSame(5, $topicProgress['unique_questions']);
        $this->assertSame('A2', $profile['cefr']['level']);
    }

    public function test_topic_recommendations_batch_recent_history_and_candidate_resource_queries(): void
    {
        $user = User::factory()->create();
        $firstTopic = Topic::query()->create(['name' => 'Articles', 'slug' => 'articles']);
        $secondTopic = Topic::query()->create(['name' => 'Prepositions', 'slug' => 'prepositions']);
        $thirdTopic = Topic::query()->create(['name' => 'Conditionals', 'slug' => 'conditionals']);

        $recentResource = Resource::factory()->create(['type' => 'article', 'status' => 'published', 'published_at' => now()]);
        $recentResource->topics()->attach($firstTopic);
        $recentResource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $recommendedResource = Resource::factory()->create(['type' => 'article', 'status' => 'published', 'published_at' => now()]);
        $recommendedResource->topics()->attach($firstTopic);
        $recommendedResource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $secondTopicResource = Resource::factory()->create(['type' => 'article', 'status' => 'published', 'published_at' => now()]);
        $secondTopicResource->topics()->attach($secondTopic);
        $secondTopicResource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $thirdTopicResource = Resource::factory()->create(['type' => 'article', 'status' => 'published', 'published_at' => now()]);
        $thirdTopicResource->topics()->attach($thirdTopic);
        $thirdTopicResource->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        LearningAttempt::query()->create([
            'user_id' => $user->id,
            'resource_id' => $recentResource->id,
            'status' => 'completed',
            'completed_at' => now()->subDay(),
        ]);

        $connection = DB::connection();
        $connection->enableQueryLog();

        $method = new \ReflectionMethod(LearnerProgressService::class, 'recommendations');
        $recommendations = $method->invoke(app(LearnerProgressService::class), $user, [
            ['id' => $firstTopic->id, 'name' => 'Articles', 'mastery_state' => 'needs_practice', 'mastery_percentage' => 50.0],
            ['id' => $secondTopic->id, 'name' => 'Prepositions', 'mastery_state' => 'needs_practice', 'mastery_percentage' => 55.0],
            ['id' => $thirdTopic->id, 'name' => 'Conditionals', 'mastery_state' => 'needs_practice', 'mastery_percentage' => 60.0],
        ], []);
        $queries = count($connection->getQueryLog());
        $connection->flushQueryLog();
        if (getenv('PERFORMANCE_PROFILE_OUTPUT') === '1') {
            fwrite(STDOUT, 'RECOMMENDATION_QUERY_COUNT '.$queries.PHP_EOL);
        }

        $this->assertSame([
            $recommendedResource->translated('title'),
            $secondTopicResource->translated('title'),
            $thirdTopicResource->translated('title'),
        ], array_column($recommendations, 'title'));
        $this->assertLessThanOrEqual(7, $queries, 'Topic recommendation queries should remain batched across three weak topics.');
    }

    public function test_lesson_completion_without_scored_evidence_does_not_create_mastery(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::query()->create(['title' => 'Progress path', 'slug' => 'progress-path', 'status' => 'draft']);
        $unit = LearningUnit::query()->create(['learning_path_id' => $path->id, 'title' => 'Unit', 'sort_order' => 1]);
        $lesson = LearningLesson::query()->create([
            'learning_unit_id' => $unit->id, 'title' => 'Completed lesson', 'slug' => 'completed-lesson',
            'status' => 'draft', 'sort_order' => 1,
        ]);
        LessonProgress::query()->create([
            'learning_lesson_id' => $lesson->id, 'user_id' => $user->id,
            'status' => 'completed', 'completed_at' => now(),
        ]);
        $profile = app(LearnerProgressService::class)->summary($user);

        $this->assertSame(1, $profile['stats']['completed_lessons']);
        $this->assertSame([], $profile['skills']);
        $this->assertSame([], $profile['topics']);
        $this->assertNull($profile['cefr']['level']);
    }

    public function test_teacher_summary_is_limited_to_completed_attempts_in_their_class_group(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $learner = User::factory()->create(['role' => 'learner']);
        $skill = Skill::query()->create(['name' => 'Vocabulary', 'slug' => 'vocabulary']);
        $topic = Topic::query()->create(['name' => 'Travel', 'slug' => 'travel']);
        $test = Assessment::factory()->create(['skill_id' => $skill->id]);
        $section = TestSection::query()->create(['test_id' => $test->id, 'title' => 'Vocabulary']);
        $question = Question::query()->create([
            'test_section_id' => $section->id, 'type' => 'single_choice', 'question' => 'Travel question',
            'points' => 1, 'difficulty' => 'medium', 'sort_order' => 1,
        ]);
        $correct = QuestionOption::query()->create([
            'question_id' => $question->id, 'value' => 'Correct', 'label' => 'Correct', 'is_correct' => true,
        ]);
        QuestionOption::query()->create([
            'question_id' => $question->id, 'value' => 'Wrong', 'label' => 'Wrong', 'is_correct' => false,
        ]);
        $question->topics()->attach($topic);
        $test->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $group = ClassGroup::query()->create(['name' => 'Group A', 'teacher_id' => $teacher->id]);
        app(ClassGroupService::class)->join($learner, $group);
        $attemptService = app(TestAttemptService::class);
        $groupAttempt = $attemptService->start($test, [
            'user_id' => $learner->id, 'class_group_id' => $group->id, 'started_at' => now(),
        ]);
        $attemptService->recordAnswer($groupAttempt, $question->id, $correct->id, $learner->id, null);
        $attemptService->complete($groupAttempt, $learner->id, null);
        $outsideAttempt = $attemptService->start($test, ['user_id' => $learner->id, 'started_at' => now()]);
        $attemptService->recordAnswer($outsideAttempt, $question->id, $correct->id, $learner->id, null);
        $attemptService->complete($outsideAttempt, $learner->id, null);

        $summary = app(LearnerProgressService::class)->classGroupSummary($group);
        $this->assertCount(1, $summary);
        $this->assertSame(1, $summary[0]['attempt_count']);
        $this->assertSame(1, $summary[0]['question_count']);
        $this->actingAs($teacher)->get(route('classes.results', $group->join_code))
            ->assertOk()->assertSee($learner->name)->assertSee('Călătorii');
        $this->actingAs($learner)->get(route('classes.results', $group->join_code))->assertForbidden();
    }

    public function test_dashboard_exposes_active_learning_path_progress_and_next_lesson_objectives(): void
    {
        $user = User::factory()->create();
        $path = LearningPath::query()->create(['title' => 'A1 path', 'slug' => 'a1-path', 'status' => 'draft']);
        $unit = LearningUnit::query()->create(['learning_path_id' => $path->id, 'title' => 'Unit', 'sort_order' => 1, 'is_published' => true]);
        $resource = Resource::factory()->create(['type' => 'article', 'status' => 'published', 'published_at' => now()]);
        $resource->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $lesson = LearningLesson::query()->create([
            'learning_unit_id' => $unit->id, 'title' => 'First lesson', 'slug' => 'first-lesson',
            'objectives' => ['Use greetings'], 'status' => 'draft', 'published_at' => now(), 'sort_order' => 1,
        ]);
        LessonActivity::query()->create([
            'learning_lesson_id' => $lesson->id, 'type' => 'resource', 'resource_id' => $resource->id,
            'title' => 'Read', 'sort_order' => 1, 'is_required' => true,
        ]);
        $lesson->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $lesson->update(['status' => 'published']);
        for ($index = 2; $index <= 10; $index++) {
            $additionalLesson = LearningLesson::query()->create([
                'learning_unit_id' => $unit->id, 'title' => 'Lesson '.$index, 'slug' => 'lesson-'.$index,
                'status' => 'draft', 'published_at' => now(), 'sort_order' => $index,
            ]);
            LessonActivity::query()->create([
                'learning_lesson_id' => $additionalLesson->id, 'type' => 'resource', 'resource_id' => $resource->id,
                'title' => 'Read', 'sort_order' => 1, 'is_required' => true,
            ]);
            $additionalLesson->approveEditorialReview(User::factory()->create(['role' => 'admin']));
            $additionalLesson->update(['status' => 'published']);
        }
        $path->update(['status' => 'published', 'published_at' => now()]);
        LearningPathEnrollment::query()->create([
            'learning_path_id' => $path->id, 'user_id' => $user->id, 'status' => 'active', 'activated_at' => now(),
        ]);

        $connection = DB::connection();
        $connection->enableQueryLog();
        $profile = app(LearnerProgressService::class)->summary($user);
        $queries = count($connection->getQueryLog());
        $connection->flushQueryLog();
        if (getenv('PERFORMANCE_PROFILE_OUTPUT') === '1') {
            fwrite(STDOUT, 'LEARNING_PATH_QUERY_COUNT '.$queries.PHP_EOL);
        }
        $this->assertLessThanOrEqual(40, $queries, 'Learning-path availability checks should eager load question and activity relations for all lessons.');
        $this->assertCount(1, $profile['learning_paths']);
        $this->assertSame(10, $profile['learning_paths'][0]['available_lessons']);
        $this->assertSame(0, $profile['learning_paths'][0]['completed_lessons']);
        $this->assertSame('First lesson', $profile['learning_paths'][0]['next_lesson']['title']);
        $this->assertSame(['Use greetings'], $profile['learning_paths'][0]['next_lesson']['objectives']);
    }

    public function test_vocabulary_items_are_deduplicated_and_review_schedule_is_deterministic(): void
    {
        $user = User::factory()->create();
        $resource = Resource::factory()->create([
            'type' => 'card', 'status' => 'published', 'published_at' => now(),
        ]);
        $deck = CardResource::query()->create(['resource_id' => $resource->id, 'instructions' => 'Review']);
        ResourceCard::query()->create([
            'card_resource_id' => $deck->id, 'front' => 'To give up', 'back' => 'To stop trying', 'sort_order' => 1,
        ]);
        $resource->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $service = app(VocabularyReviewService::class);
        $service->encounterResource($user, $resource);
        $service->encounterResource($user, $resource);

        $duplicateResource = Resource::factory()->create([
            'type' => 'card', 'status' => 'published', 'published_at' => now(),
        ]);
        $duplicateDeck = CardResource::query()->create(['resource_id' => $duplicateResource->id, 'instructions' => 'Another deck']);
        ResourceCard::query()->create([
            'card_resource_id' => $duplicateDeck->id, 'front' => 'To give up', 'back' => 'To stop trying', 'sort_order' => 1,
        ]);
        $duplicateResource->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $service->encounterResource($user, $duplicateResource);

        $this->assertSame(1, LearnerVocabularyItem::query()->where('user_id', $user->id)->count());

        $lessonUser = User::factory()->create();
        $path = LearningPath::query()->create(['title' => 'Vocabulary path', 'slug' => 'vocabulary-path', 'status' => 'draft']);
        $unit = LearningUnit::query()->create(['learning_path_id' => $path->id, 'title' => 'Vocabulary unit', 'sort_order' => 1]);
        $lesson = LearningLesson::query()->create([
            'learning_unit_id' => $unit->id, 'title' => 'Vocabulary lesson', 'slug' => 'vocabulary-lesson',
            'status' => 'draft', 'sort_order' => 1,
        ]);
        LessonActivity::query()->create([
            'learning_lesson_id' => $lesson->id, 'type' => 'resource', 'resource_id' => $resource->id,
            'title' => 'Flashcards', 'sort_order' => 1,
        ]);
        $this->assertSame(1, $service->encounterLesson($lessonUser, $lesson));
        $this->assertDatabaseHas('learner_vocabulary_items', ['user_id' => $lessonUser->id, 'prompt' => 'To give up']);

        $vocabularySkill = Skill::query()->create(['name' => 'Vocabulary', 'slug' => 'vocabulary']);
        $vocabularyTest = Assessment::factory()->create(['skill_id' => $vocabularySkill->id]);
        $section = TestSection::query()->create(['test_id' => $vocabularyTest->id, 'title' => 'Matching']);
        Question::query()->create([
            'test_section_id' => $section->id, 'type' => 'matching', 'question' => 'Match the words',
            'metadata' => ['pairs' => [['left' => 'bonjour', 'right' => 'hello']]],
            'points' => 1, 'difficulty' => 'easy', 'sort_order' => 1,
        ]);
        $vocabularyTest->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        LessonActivity::query()->create([
            'learning_lesson_id' => $lesson->id, 'type' => 'test', 'test_id' => $vocabularyTest->id,
            'title' => 'Matching exercise', 'sort_order' => 2,
        ]);
        $lesson->unsetRelation('activities');
        $this->assertSame(1, $service->encounterLesson($lessonUser, $lesson));
        $this->assertDatabaseHas('learner_vocabulary_items', ['user_id' => $lessonUser->id, 'prompt' => 'bonjour', 'answer' => 'hello']);

        $item = LearnerVocabularyItem::query()->where('user_id', $user->id)->firstOrFail();

        $service->review($user, $item, 'good');
        $this->assertSame(86400, $item->fresh()->interval_seconds);
        $this->assertSame(1, $item->fresh()->repetitions);

        $service->review($user, $item->fresh(), 'again');
        $this->assertSame(600, $item->fresh()->interval_seconds);
        $this->assertSame(0, $item->fresh()->repetitions);
        $this->assertSame(1, $item->fresh()->lapses);
        $this->assertDatabaseCount('vocabulary_review_events', 2);
        $this->assertDatabaseCount('experience_point_awards', 1);
        $this->assertDatabaseHas('experience_point_awards', ['user_id' => $user->id, 'source_type' => 'vocabulary_review', 'points' => 2]);
    }

    public function test_adaptive_practice_uses_a_separate_session_and_does_not_create_formal_test_attempts(): void
    {
        $user = User::factory()->create();
        $skill = Skill::query()->create(['name' => 'Grammar', 'slug' => 'grammar']);
        $topic = Topic::query()->create(['name' => 'Articles', 'slug' => 'articles']);
        $test = Assessment::factory()->create(['skill_id' => $skill->id]);
        $test->approveEditorialReview(User::factory()->create(['role' => 'admin']));
        $section = TestSection::query()->create(['test_id' => $test->id, 'title' => 'Practice']);
        $questions = collect();

        for ($index = 1; $index <= 11; $index++) {
            $question = Question::query()->create([
                'test_section_id' => $section->id, 'type' => 'single_choice',
                'question' => 'Practice question '.$index, 'points' => 1,
                'difficulty' => $index === 3 ? 'medium' : 'easy', 'sort_order' => $index,
            ]);
            $correct = QuestionOption::query()->create([
                'question_id' => $question->id, 'value' => 'Correct '.$index,
                'label' => 'Correct '.$index, 'is_correct' => true, 'sort_order' => 1,
            ]);
            QuestionOption::query()->create([
                'question_id' => $question->id, 'value' => 'Wrong '.$index,
                'label' => 'Wrong '.$index, 'is_correct' => false, 'sort_order' => 2,
            ]);
            $question->topics()->attach($topic);
            $questions->push([$question, $correct]);
        }

        $test->refresh()->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $service = app(AdaptivePracticeService::class);
        $session = $service->start($user);
        $this->actingAs($user)->get(route('practice.show', $session))->assertOk()->assertSee('Sesiune de exerciții');
        $this->assertSame('easy', $session->difficulty_min);
        $this->assertSame('medium', $session->difficulty_max);
        $payload = $service->questionPayload($service->session($session, $user));
        $this->assertCount(10, $payload);
        $this->assertArrayNotHasKey('is_correct', $payload[0]['options'][0]);

        foreach (array_slice($questions->all(), 0, 10) as [$question, $correctOption]) {
            $this->assertTrue($service->recordResponse($session, $user, $question->id, (string) $correctOption->id));
        }
        $completed = $service->complete($session, $user);

        $this->assertSame('completed', $completed->status);
        $this->assertSame(10, $completed->total_points);
        $this->assertSame(10.0, (float) $completed->earned_points);
        $this->assertSame(100.0, (float) $completed->percentage);
        $this->assertDatabaseCount('adaptive_practice_sessions', 1);
        $this->assertDatabaseCount('test_attempts', 0);
        $this->assertDatabaseHas('experience_point_awards', ['user_id' => $user->id, 'source_type' => 'adaptive_practice', 'source_id' => $session->id, 'points' => 10]);

        $review = $service->questionPayload($service->session($completed, $user));
        $this->assertArrayHasKey('is_correct', $review[0]['options'][0]);
        $this->actingAs($user)->get(route('practice.show', $completed))->assertOk()->assertSee('Verificarea răspunsurilor')->assertSee('Ai câștigat 10 XP pentru această sesiune.');
        $nextSession = $service->start($user);
        $this->assertCount(1, $nextSession->question_order);
        $remainingId = $nextSession->question_order[0];
        [$remainingQuestion, $remainingOption] = $questions->first(fn (array $pair): bool => $pair[0]->id === $remainingId);
        $this->actingAs($user)->post(route('practice.submit', $nextSession), [
            'answers' => [$remainingQuestion->id => (string) $remainingOption->id],
        ])->assertRedirect(route('practice.show', $nextSession));
        $nextSession->refresh();
        $this->assertSame('completed', $nextSession->status);
        $this->assertSame(20, (int) ExperiencePointAward::query()->where('user_id', $user->id)->sum('points'));

        $profile = app(LearnerProgressService::class)->summary($user);
        $topicProgress = collect($profile['topics'])->firstWhere('id', $topic->id);
        $this->assertSame(100.0, $topicProgress['mastery_percentage']);
        $this->assertSame(11, $topicProgress['unique_questions']);
        $this->assertSame(2, $topicProgress['attempt_count']);
        $this->assertSame(2, $profile['stats']['completed_adaptive_sessions']);
        $this->assertNull($profile['cefr']['level']);
    }

    public function test_dashboard_and_vocabulary_review_pages_render_for_authenticated_learners(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertSee('Progres în funcție de competență')->assertSee('Progres în funcție de subiect')->assertSee('Pașii următori recomandați');
        $this->actingAs($user)->get(route('reviews.index'))->assertOk()
            ->assertSee('Recapitularea vocabularului')->assertSee('Gata de recapitulare');
    }

    public function test_vocabulary_review_and_adaptive_sessions_are_private_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $item = LearnerVocabularyItem::query()->create([
            'user_id' => $owner->id, 'source_type' => 'resource_card', 'source_id' => 1,
            'item_key' => hash('sha256', 'private-item'), 'prompt' => 'Prompt', 'answer' => 'Answer',
            'due_at' => now(),
        ]);
        $session = AdaptivePracticeSession::query()->create([
            'user_id' => $owner->id, 'question_order' => [], 'status' => 'started',
            'difficulty_min' => 'easy', 'difficulty_max' => 'medium', 'started_at' => now(),
        ]);

        $this->actingAs($other)->post(route('reviews.answer', $item), ['grade' => 'good'])->assertNotFound();
        $this->actingAs($other)->get(route('practice.show', $session))->assertNotFound();
    }
}
