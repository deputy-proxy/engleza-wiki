<?php

namespace App\Livewire\Tests;

use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Services\Test\TestScoringService;
use Livewire\Component;

class TakeTest extends Component
{
    public Test $test;

    public TestAttempt $attempt;

    public array $questions = [];

    public int $currentIndex = 0;

    public array $answers = []; // [questionId => optionId]

    public function mount(Test $test, TestAttempt $attempt): void
    {
        abort_unless($attempt->test_id === $test->id, 404);
        if ($attempt->user_id) {
            abort_unless(auth()->id() === $attempt->user_id, 403);
        } else {
            abort_unless(session('anon_session') === $attempt->session_id, 403);
        }
        if ($attempt->status === 'completed') {
            $this->redirectRoute('tests.result', ['test' => $test->slug, 'attempt' => $attempt->id]);

            return;
        }
        $this->test = $test;
        $this->attempt = $attempt;
        $qs = $test->allQuestions()->load('options')->map(function ($q) {
            return [
                'id' => $q->id,
                'text' => $q->question,
                'section' => $q->section?->title,
                'options' => $q->options->map(fn ($o) => ['id' => $o->id, 'label' => $o->label, 'value' => $o->value])->all(),
            ];
        });
        $this->questions = $qs->all();
        // load existing answers
        $prev = TestAnswer::where('test_attempt_id', $attempt->id)->get();
        foreach ($prev as $p) {
            $this->answers[$p->question_id] = $p->question_option_id;
        }
    }

    public function selectOption(int $questionId, int $optionId): void
    {
        // verify option belongs to question
        $ok = QuestionOption::where('id', $optionId)->where('question_id', $questionId)->exists();
        if (! $ok) {
            return;
        }
        $this->answers[$questionId] = $optionId;
        TestAnswer::updateOrCreate(
            ['test_attempt_id' => $this->attempt->id, 'question_id' => $questionId],
            ['question_option_id' => $optionId, 'answered_at' => now()]
        );
    }

    public function next(): void
    {
        if ($this->currentIndex < count($this->questions) - 1) {
            $this->currentIndex++;
        }
    }

    public function prev(): void
    {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
        }
    }

    public function jumpTo(int $i): void
    {
        if ($i >= 0 && $i < count($this->questions)) {
            $this->currentIndex = $i;
        }
    }

    public function submit(TestScoringService $scoring): void
    {
        if ($this->attempt->status === 'completed') {
            return;
        }
        $scoring->score($this->attempt->fresh());
        $this->redirectRoute('tests.result', ['test' => $this->test->slug, 'attempt' => $this->attempt->id]);
    }

    public function render()
    {
        return view('livewire.tests.take-test');
    }
}
