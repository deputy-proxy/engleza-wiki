<?php

namespace App\Livewire\Resources;

use App\Models\LearningAttempt;
use App\Models\Resource;
use App\Services\Learning\LearningAttemptService;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ExerciseRunner extends Component
{
    #[Locked]
    public Resource $resource;

    #[Locked]
    public ?LearningAttempt $attempt = null;

    #[Locked]
    public array $questions = [];

    #[Locked]
    public array $answers = [];

    #[Locked]
    public bool $submitted = false;

    #[Locked]
    public bool $showCorrectAnswers = true;

    public function mount(Resource $resource, LearningAttemptService $attempts): void
    {
        $this->resource = $resource;
        $settings = $resource->type === 'listening' ? $resource->listeningResource : $resource->readingResource;
        $this->showCorrectAnswers = (bool) ($settings->show_correct_answers ?? true);
        $activeAttemptKey = 'active_learning_attempts.'.$resource->id;
        $activeAttemptId = session()->get($activeAttemptKey);
        if ($activeAttemptId) {
            $activeAttempt = LearningAttempt::query()->whereKey($activeAttemptId)->first();
            if ($activeAttempt && $activeAttempt->resource_id === $resource->id && in_array($activeAttempt->status, ['started', 'completed'], true)) {
                $attempts->authorize($activeAttempt, auth()->id(), session('anon_session'));
                $this->attempt = $activeAttempt;
                $this->submitted = $activeAttempt->status === 'completed';
                if ($this->submitted) {
                    session()->forget($activeAttemptKey);
                }
            } else {
                session()->forget($activeAttemptKey);
            }
        }
        $this->questions = $attempts->questionPayload($resource, $this->submitted, $this->attempt);
        $this->answers = $this->attempt ? $attempts->answersForAttempt($this->attempt) : [];
        if ($this->submitted) {
            $this->attempt->load(['answers.question', 'answers.option']);
        }
    }

    public function pick(int $questionId, int $optionId, LearningAttemptService $attempts): void
    {
        $this->setAnswer($questionId, $optionId, $attempts);
    }

    public function setAnswer(int $questionId, mixed $response, LearningAttemptService $attempts): void
    {
        if ($this->submitted) {
            return;
        }
        $this->ensureAttempt($attempts);
        if (! $this->attempt) {
            return;
        }
        if ($attempts->recordResponse($this->attempt, $questionId, $response, auth()->id(), session('anon_session'))) {
            $this->answers[$questionId] = $response;
        }
    }

    public function toggleOption(int $questionId, int $optionId, LearningAttemptService $attempts): void
    {
        $selected = array_values(array_map('intval', (array) ($this->answers[$questionId] ?? [])));
        $selected = in_array($optionId, $selected, true)
            ? array_values(array_diff($selected, [$optionId]))
            : [...$selected, $optionId];
        $this->setAnswer($questionId, $selected, $attempts);
    }

    public function setStructuredAnswer(int $questionId, string $key, string $value, LearningAttemptService $attempts): void
    {
        $response = (array) ($this->answers[$questionId] ?? []);
        if ($value === '') {
            unset($response[$key]);
        } else {
            $response[$key] = $value;
        }
        $this->setAnswer($questionId, $response, $attempts);
    }

    public function submit(LearningAttemptService $attempts): void
    {
        if ($this->submitted) {
            return;
        }
        $this->ensureAttempt($attempts);
        if (! $this->attempt) {
            return;
        }
        $this->attempt = $attempts->complete($this->attempt, auth()->id(), session('anon_session'));
        $this->questions = $attempts->questionPayload($this->resource, revealAnswers: true, attempt: $this->attempt);
        $this->answers = $attempts->answersForAttempt($this->attempt);
        $this->attempt->load(['answers.question', 'answers.option']);
        $this->submitted = true;
        session()->forget('active_learning_attempts.'.$this->resource->id);
    }

    private function ensureAttempt(LearningAttemptService $attempts): void
    {
        if ($this->attempt) {
            return;
        }
        if (! auth()->check() && ! session()->has('anon_session')) {
            session(['anon_session' => (string) Str::uuid()]);
        }
        $this->attempt = $attempts->start($this->resource, auth()->id(), session('anon_session'));
        session()->put('active_learning_attempts.'.$this->resource->id, $this->attempt->id);
    }

    public function render()
    {
        return view('livewire.resources.exercise-runner');
    }
}
