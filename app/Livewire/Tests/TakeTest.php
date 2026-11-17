<?php

namespace App\Livewire\Tests;

use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Test\TestAttemptService;
use Livewire\Attributes\Locked;
use Livewire\Component;

class TakeTest extends Component
{
    #[Locked]
    public Test $test;

    #[Locked]
    public TestAttempt $attempt;

    #[Locked]
    public array $questions = [];

    #[Locked]
    public int $currentIndex = 0;

    #[Locked]
    public array $answers = [];

    public function mount(Test $test, TestAttempt $attempt, TestAttemptService $attempts): void
    {
        abort_unless($attempt->test_id === $test->id, 404);
        $attempts->authorize($attempt, auth()->id(), session('anon_session'));
        if ($attempt->status === 'started' && $test->duration_minutes !== null && $attempt->started_at !== null
            && now()->greaterThanOrEqualTo($attempt->started_at->copy()->addMinutes($test->duration_minutes))) {
            $attempts->complete($attempt, auth()->id(), session('anon_session'));
            $attempt->refresh();
        }
        if (in_array($attempt->status, ['completed', 'expired'], true)) {
            $this->redirectRoute('tests.result', ['test' => $test->slug, 'attempt' => $attempt->id]);

            return;
        }
        $this->test = $test;
        $this->attempt = $attempt;
        $this->questions = $attempts->questionPayload($attempt);
        $this->answers = $attempt->answers()->pluck('question_option_id', 'question_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function selectOption(int $questionId, int $optionId, TestAttemptService $attempts): void
    {
        if (! $attempts->recordAnswer($this->attempt, $questionId, $optionId, auth()->id(), session('anon_session'))) {
            if ($this->attempt->fresh()->status === 'expired') {
                $attempts->complete($this->attempt, auth()->id(), session('anon_session'));
                $this->redirectRoute('tests.result', ['test' => $this->test->slug, 'attempt' => $this->attempt->id]);
            }

            return;
        }
        $this->answers[$questionId] = $optionId;
    }

    public function next(): void
    {
        $this->currentIndex = min($this->currentIndex + 1, max(0, count($this->questions) - 1));
    }

    public function prev(): void
    {
        $this->currentIndex = max(0, $this->currentIndex - 1);
    }

    public function jumpTo(int $index): void
    {
        if ($index >= 0 && $index < count($this->questions)) {
            $this->currentIndex = $index;
        }
    }

    public function submit(TestAttemptService $attempts): void
    {
        $attempts->complete($this->attempt, auth()->id(), session('anon_session'));
        $this->redirectRoute('tests.result', ['test' => $this->test->slug, 'attempt' => $this->attempt->id]);
    }

    public function render()
    {
        return view('livewire.tests.take-test');
    }
}
