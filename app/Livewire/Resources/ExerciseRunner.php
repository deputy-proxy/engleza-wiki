<?php
namespace App\Livewire\Resources;

use App\Models\{Resource, LearningAttempt, LearningAnswer, QuestionOption};
use App\Services\Learning\LearningScoringService;
use Livewire\Component;
use Illuminate\Support\Str;

class ExerciseRunner extends Component {
    public Resource $resource;
    public ?LearningAttempt $attempt = null;
    public array $questions = [];
    public array $answers = [];
    public bool $submitted = false;

    public function mount(Resource $resource): void {
        $this->resource = $resource;
        $rel = $resource->type === 'listening' ? $resource->listeningResource : $resource->readingResource;
        if (!$rel) { $this->questions = []; return; }
        $qs = $rel->questions()->with('options')->orderBy('sort_order')->get();
        $this->questions = $qs->map(fn($q)=>['id'=>$q->id,'text'=>$q->question,'explanation'=>$q->explanation,'options'=>$q->options->map(fn($o)=>['id'=>$o->id,'label'=>$o->label,'value'=>$o->value,'is_correct'=>$o->is_correct])->all()])->all();
    }

    public function pick(int $questionId, int $optionId): void {
        $ok = QuestionOption::where('id',$optionId)->where('question_id',$questionId)->exists();
        if ($ok) $this->answers[$questionId] = $optionId;
    }

    public function submit(LearningScoringService $scoring): void {
        if ($this->submitted) return;
        if (!session()->has('anon_session')) session(['anon_session'=>Str::uuid()->toString()]);
        $attempt = LearningAttempt::create([
            'resource_id'=>$this->resource->id,
            'user_id'=>auth()->id(),
            'session_id'=>auth()->check()? null : session('anon_session'),
            'started_at'=>now(),'status'=>'started',
        ]);
        foreach ($this->answers as $qid=>$oid) {
            LearningAnswer::create(['learning_attempt_id'=>$attempt->id,'question_id'=>$qid,'question_option_id'=>$oid,'answered_at'=>now()]);
        }
        $scoring->score($attempt->fresh());
        $this->attempt = $attempt->fresh(['answers.question','answers.option']);
        $this->submitted = true;
    }

    public function render() {
        return view('livewire.resources.exercise-runner');
    }
}
