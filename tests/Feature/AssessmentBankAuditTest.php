<?php

namespace Tests\Feature;

use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Skill;
use App\Models\Test as Assessment;
use App\Models\TestSection;
use App\Models\Topic;
use App\Models\User;
use App\Services\Assessment\AssessmentBankAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentBankAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_bank_audit_detects_reused_prompts_invalid_options_and_missing_metadata(): void
    {
        $levelA1 = Level::query()->create(['name' => 'A1', 'slug' => 'a1', 'sort_order' => 1]);
        $levelA2 = Level::query()->create(['name' => 'A2', 'slug' => 'a2', 'sort_order' => 2]);
        $firstTest = Assessment::factory()->create([
            'title' => 'English Level Test A1', 'slug' => 'english-level-test-a1',
            'level_id' => $levelA1->id, 'duration_minutes' => 15,
        ]);
        $secondTest = Assessment::factory()->create([
            'title' => 'English Level Test A2', 'slug' => 'english-level-test-a2',
            'level_id' => $levelA2->id, 'duration_minutes' => 20,
        ]);
        $firstSection = TestSection::query()->create(['test_id' => $firstTest->id, 'title' => 'Grammar', 'sort_order' => 1]);
        $secondSection = TestSection::query()->create(['test_id' => $secondTest->id, 'title' => 'Grammar', 'sort_order' => 1]);
        $firstQuestion = $this->createQuestion($firstSection, '  She   goes to school every day. ', 'easy');
        $secondQuestion = $this->createQuestion($secondSection, 'she goes to school every day.', 'medium');
        QuestionOption::query()->create([
            'question_id' => $firstQuestion->id, 'label' => 'A', 'value' => 'goes', 'is_correct' => true, 'sort_order' => 1,
        ]);
        QuestionOption::query()->create([
            'question_id' => $secondQuestion->id, 'label' => 'A', 'value' => 'goes', 'is_correct' => true, 'sort_order' => 1,
        ]);

        $result = app(AssessmentBankAuditService::class)->audit(includeGrandfathered: true);

        $this->assertSame(2, $result['summary']['tests']);
        $this->assertSame(2, $result['summary']['questions']);
        $this->assertCount(1, $result['duplicates']);
        $this->assertTrue($result['tests'][0]['items'][0]['reused_across_forms']);
        $this->assertCount(2, $result['tests'][0]['items'][0]['used_in_forms']);
        $this->assertSame('A1', $result['tests'][0]['items'][0]['level']);
        $this->assertSame('Grammar', $result['tests'][0]['items'][0]['skill']);
        $this->assertSame('single_choice', $result['tests'][0]['items'][0]['type']);
        $this->assertContains('prompt_reused_across_forms', array_column($result['issues'], 'code'));
        $this->assertContains('invalid_question_definition', array_column($result['issues'], 'code'));
        $this->assertContains('missing_explanation', array_column($result['issues'], 'code'));
        $this->assertContains('missing_topic', array_column($result['issues'], 'code'));
        $this->assertContains('missing_cefr_rationale', array_column($result['issues'], 'code'));
        $this->assertSame('error', collect($result['issues'])->firstWhere('code', 'missing_explanation')['severity']);
        $this->assertContains('blueprint_question_count', array_column($result['issues'], 'code'));
    }

    public function test_assessment_bank_audit_excludes_grandfathered_demo_tests_by_default(): void
    {
        $test = Assessment::factory()->create(['slug' => 'legacy-demo-test']);
        $test->forceFill(['editorial_status' => 'grandfathered'])->saveQuietly();

        $result = app(AssessmentBankAuditService::class)->audit();

        $this->assertSame(0, $result['summary']['tests']);
        $this->assertSame([], $result['tests']);
    }

    public function test_level_test_detail_discloses_its_indicative_scope_and_limitations(): void
    {
        $test = Assessment::factory()->create([
            'title' => 'English Level Test A1',
            'slug' => 'english-level-test-a1',
        ]);
        $test->approveEditorialReview(User::factory()->create(['role' => 'admin']));

        $this->get(route('tests.show', $test))
            ->assertOk()
            ->assertSee('Aceasta este o estimare orientativă obținută prin exersare')
            ->assertSee('nu evaluează ascultarea, vorbirea sau scrierea');
    }

    public function test_assessment_bank_audit_command_outputs_json_and_excludes_grandfathered_tests(): void
    {
        $test = Assessment::factory()->create(['slug' => 'legacy-demo-test']);
        $test->forceFill(['editorial_status' => 'grandfathered'])->saveQuietly();

        $this->artisan('assessment:bank-audit', ['--format' => 'json'])
            ->expectsOutputToContain('prospective records only')
            ->assertExitCode(0);
    }

    public function test_assessment_bank_audit_accepts_a_complete_balanced_a1_blueprint(): void
    {
        $level = Level::query()->create(['name' => 'A1', 'slug' => 'a1', 'sort_order' => 1]);
        $skills = collect(['Grammar', 'Vocabulary', 'Reading'])->mapWithKeys(function (string $name): array {
            $skill = Skill::query()->create(['name' => $name, 'slug' => strtolower($name)]);

            return [$name => $skill];
        });
        $test = Assessment::factory()->create([
            'title' => 'English Level Test A1', 'slug' => 'english-level-test-a1',
            'level_id' => $level->id, 'skill_id' => null, 'duration_minutes' => 15,
        ]);
        $topicNames = ['Present Simple', 'Articles', 'Prepositions', 'Vocabulary', 'Reading'];
        $topics = collect($topicNames)->mapWithKeys(function (string $name): array {
            $topic = Topic::query()->create(['name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name))]);

            return [$name => $topic];
        });
        $sectionCounts = ['Grammar' => 10, 'Vocabulary' => 6, 'Reading' => 4];
        $sectionTopics = [
            'Grammar' => ['Present Simple', 'Articles', 'Prepositions'],
            'Vocabulary' => ['Vocabulary'],
            'Reading' => ['Reading'],
        ];
        $difficultySequence = array_merge(array_fill(0, 12, 'easy'), array_fill(0, 8, 'medium'));
        $correctLabels = ['A', 'B', 'C', 'D'];
        $questionNumber = 0;

        foreach ($sectionCounts as $sectionTitle => $count) {
            $section = TestSection::query()->create([
                'test_id' => $test->id, 'title' => $sectionTitle, 'sort_order' => $questionNumber,
            ]);

            for ($index = 0; $index < $count; $index++) {
                $questionNumber++;
                $question = $this->createQuestion(
                    $section,
                    "A unique A1 {$sectionTitle} prompt number {$questionNumber}?",
                    $difficultySequence[$questionNumber - 1],
                    'A concise explanation for the correct answer.',
                );
                $question->topics()->attach($topics[$sectionTopics[$sectionTitle][$index % count($sectionTopics[$sectionTitle])]]->id);
                $correctLabel = $correctLabels[($questionNumber - 1) % count($correctLabels)];

                foreach ($correctLabels as $optionIndex => $label) {
                    QuestionOption::query()->create([
                        'question_id' => $question->id,
                        'label' => $label,
                        'value' => $label === $correctLabel ? 'Correct answer' : "Distractor {$label}",
                        'is_correct' => $label === $correctLabel,
                        'sort_order' => $optionIndex,
                    ]);
                }
            }
        }

        $result = app(AssessmentBankAuditService::class)->audit('english-level-test-a1', includeGrandfathered: true);

        $this->assertSame(0, $result['summary']['errors']);
        $this->assertSame(0, $result['summary']['warnings']);
        $this->assertSame(20, $result['tests'][0]['question_count']);
        $this->assertFalse($result['tests'][0]['items'][0]['reused_across_forms']);
        $this->assertTrue($result['tests'][0]['items'][0]['explanation_present']);
        $this->assertTrue($result['tests'][0]['items'][0]['cefr_rationale_present']);
    }

    private function createQuestion(TestSection $section, string $prompt, string $difficulty, ?string $explanation = null): Question
    {
        return Question::query()->create([
            'test_section_id' => $section->id,
            'type' => 'single_choice',
            'question' => $prompt,
            'explanation' => $explanation,
            'points' => 1,
            'difficulty' => $difficulty,
            'metadata' => $explanation === null ? [] : ['cefr_rationale' => 'This item assesses a language feature appropriate to the A1 descriptor.'],
            'sort_order' => 1,
        ]);
    }
}
