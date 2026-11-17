<?php

namespace Tests\Unit;

use App\Models\Question;
use App\Models\QuestionOption;
use App\Services\Assessment\QuestionResponseService;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;

class QuestionResponseServiceTest extends TestCase
{
    private QuestionResponseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QuestionResponseService;
    }

    public function test_single_and_true_false_responses_are_validated_against_the_question_options(): void
    {
        $question = $this->question('single_choice', [
            $this->option(1, 'A', true), $this->option(2, 'B', false),
        ]);

        self::assertTrue($this->service->evaluate($question, 1)['correct']);
        self::assertFalse($this->service->evaluate($question, 999)['valid']);

        $trueFalse = $this->question('true_false', [
            $this->option(3, 'True', true), $this->option(4, 'False', false),
        ]);
        self::assertTrue($this->service->evaluate($trueFalse, '3')['correct']);
    }

    public function test_multiple_choice_uses_partial_credit_and_rejects_foreign_options(): void
    {
        $question = $this->question('multiple_choice', [
            $this->option(1, 'A', true), $this->option(2, 'B', true), $this->option(3, 'C', false),
        ]);

        self::assertSame(2.0, $this->service->evaluate($question, [1])['earned_points']);
        self::assertSame(0.0, $this->service->evaluate($question, [1, 3])['earned_points']);
        self::assertFalse($this->service->evaluate($question, [999])['valid']);
    }

    public function test_text_answers_are_normalized_without_exposing_accepted_answers(): void
    {
        $question = $this->question('text', [
            $this->option(1, 'Correct Answer', true),
            $this->option(2, 'Wrong Answer', false),
        ]);

        self::assertTrue($this->service->evaluate($question, '  CORRECT   answer ')['correct']);
        self::assertSame([], $this->service->learnerConfiguration($question));
        self::assertSame(['accepted_answers' => ['Correct Answer']], $this->service->answerKey($question));
    }

    public function test_matching_ordering_and_gap_fill_support_partial_credit(): void
    {
        $matching = $this->question('matching', [], ['pairs' => [
            ['left' => 'cat', 'right' => 'gato'], ['left' => 'dog', 'right' => 'perro'],
        ]]);
        self::assertSame(2.0, $this->service->evaluate($matching, ['cat' => 'gato'])['earned_points']);

        $ordering = $this->question('ordering', [], ['items' => ['first', 'second', 'third']]);
        self::assertEqualsWithDelta(1.33, $this->service->evaluate($ordering, [0 => 'first'])['earned_points'], 0.001);

        $gapFill = $this->question('gap_fill', [], ['gaps' => [
            'gap_1' => ['is', 'was'], 'gap_2' => 'are',
        ]]);
        self::assertSame(2.0, $this->service->evaluate($gapFill, ['gap_1' => ' IS '])['earned_points']);
    }

    public function test_invalid_structured_definitions_and_responses_are_rejected(): void
    {
        self::assertNotEmpty($this->service->metadataIssues('matching', ['pairs' => [['left' => 'cat', 'right' => 'gato']]]));
        self::assertNotEmpty($this->service->metadataIssues('ordering', ['items' => ['first', 'first']]));
        self::assertNotEmpty($this->service->metadataIssues('gap_fill', ['gaps' => ['blank' => []]]));

        $question = $this->question('matching', [], ['pairs' => [
            ['left' => 'cat', 'right' => 'gato'], ['left' => 'dog', 'right' => 'perro'],
        ]]);
        self::assertFalse($this->service->evaluate($question, ['unknown' => 'gato'])['valid']);

        $ordering = $this->question('ordering', [], ['items' => ['first', 'second']]);
        self::assertFalse($this->service->evaluate($ordering, ['wrong' => 'first'])['valid']);

        $gapFill = $this->question('gap_fill', [], ['gaps' => ['gap_1' => 'answer']]);
        self::assertFalse($this->service->evaluate($gapFill, ['gap_1' => ['nested']])['valid']);
    }

    public function test_question_definition_validation_checks_choice_option_configuration(): void
    {
        $question = $this->question('single_choice', [$this->option(1, 'Only option', true)]);
        self::assertNotEmpty($this->service->definitionIssues($question));

        $question = $this->question('multiple_choice', [
            $this->option(1, 'A', false), $this->option(2, 'B', false),
        ]);
        self::assertNotEmpty($this->service->definitionIssues($question));

        self::assertSame([], $this->service->metadataIssues('gap_fill', ['gaps' => ['gap_1' => ['answer', 'alternate']]]));
    }

    public function test_learner_configuration_never_contains_answer_keys(): void
    {
        $question = $this->question('gap_fill', [], ['gaps' => ['gap_1' => ['secret answer']]]);
        self::assertSame(['gaps' => ['gap_1' => '']], $this->service->learnerConfiguration($question));
        self::assertStringNotContainsString('secret answer', json_encode($this->service->learnerConfiguration($question)));
    }

    /** @param array<int, QuestionOption> $options
     * @param  array<string, mixed>  $metadata
     */
    private function question(string $type, array $options = [], array $metadata = []): Question
    {
        $question = new Question;
        $question->forceFill(['id' => 42, 'type' => $type, 'points' => 4, 'metadata' => $metadata]);
        $question->setRelation('options', new Collection($options));

        return $question;
    }

    private function option(int $id, string $value, bool $correct, ?string $label = null): QuestionOption
    {
        $option = new QuestionOption;
        $option->forceFill(['id' => $id, 'value' => $value, 'label' => $label ?? (string) $id, 'is_correct' => $correct]);

        return $option;
    }
}
