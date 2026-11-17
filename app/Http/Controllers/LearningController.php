<?php

namespace App\Http\Controllers;

use App\Models\LearningAttempt;
use App\Models\Resource;
use App\Models\WritingSubmission;
use App\Services\Learning\LearningAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningController extends Controller
{
    // Start listening/reading attempt
    public function start(Resource $resource, Request $r, LearningAttemptService $attempts)
    {
        if (! session()->has('anon_session') && ! auth()->check()) {
            session(['anon_session' => (string) Str::uuid()]);
        }
        $attempt = $attempts->start($resource, auth()->id(), session('anon_session'), $r->ip(), $r->userAgent());
        session()->put('active_learning_attempts.'.$resource->id, $attempt->id);

        return redirect()->route('resources.show', $resource->slug);
    }

    public function submit(Resource $resource, Request $r, LearningAttemptService $attempts)
    {
        abort_unless(in_array($resource->type, ['listening', 'reading'], true), 404);
        $data = $r->validate([
            'attempt_id' => ['required', 'integer'],
            'answers' => ['sometimes', 'array'],
            'answers.*' => ['required', 'integer'],
        ]);
        $attempt = LearningAttempt::query()->findOrFail((int) $data['attempt_id']);
        abort_unless($attempt->resource_id === $resource->id, 404);
        $attempts->authorize($attempt, auth()->id(), session('anon_session'));
        if ($attempt->status === 'completed') {
            return redirect()->route('resources.result', ['resource' => $resource->slug, 'attempt' => $attempt->id]);
        }
        DB::transaction(function () use ($data, $attempt, $attempts): void {
            foreach ($data['answers'] ?? [] as $questionId => $optionId) {
                abort_unless($attempts->recordAnswer($attempt, (int) $questionId, (int) $optionId, auth()->id(), session('anon_session')), 422);
            }
            $attempts->complete($attempt, auth()->id(), session('anon_session'));
        });
        session()->forget('active_learning_attempts.'.$attempt->resource_id);

        return redirect()->route('resources.result', ['resource' => $resource->slug, 'attempt' => $attempt->id]);
    }

    public function result(Resource $resource, LearningAttempt $attempt, LearningAttemptService $attempts)
    {
        abort_unless(in_array($resource->type, ['listening', 'reading'], true), 404);
        abort_unless($attempt->resource_id === $resource->id, 404);
        abort_unless($attempt->status === 'completed', 404);
        $attempts->authorize($attempt, auth()->id(), session('anon_session'));
        $attempt->load(['answers.question.options', 'answers.option']);
        $questions = $attempts->questionPayload($resource, revealAnswers: true, attempt: $attempt);
        if ($resource->type === 'listening') {
            $resource->load('listeningResource');
            $showCorrectAnswers = (bool) ($resource->listeningResource->show_correct_answers ?? true);
        } else {
            $resource->load('readingResource');
            $showCorrectAnswers = (bool) ($resource->readingResource->show_correct_answers ?? true);
        }

        return view('pages.resources.types.result', compact('resource', 'attempt', 'showCorrectAnswers', 'questions'));
    }

    // Writing submissions
    public function submitWriting(Resource $resource, Request $r)
    {
        abort_unless($resource->type === 'writing', 404);
        $wr = $resource->writingResource;
        abort_unless($wr !== null, 404);
        $data = $r->validate(['content' => 'required|string|max:20000', 'status' => 'nullable|in:draft,submitted']);
        $words = str_word_count(strip_tags($data['content']));
        $sub = WritingSubmission::create([
            'writing_resource_id' => $wr->id,
            'user_id' => auth()->id(),
            'content' => $data['content'],
            'word_count' => $words,
            'status' => $data['status'] ?? 'submitted',
            'submitted_at' => ($data['status'] ?? 'submitted') === 'submitted' ? now() : null,
        ]);

        return redirect()->route('resources.show', $resource->slug)->with('writing_submission_id', $sub->id);
    }
}
