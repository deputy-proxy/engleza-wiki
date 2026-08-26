<?php
namespace App\Http\Controllers;

use App\Models\{Resource, LearningAttempt, LearningAnswer, WritingResource, WritingSubmission};
use App\Services\Learning\LearningScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LearningController extends Controller {
    // Start listening/reading attempt
    public function start(Resource $resource, Request $r) {
        abort_unless(in_array($resource->type, ['listening','reading']), 404);
        if (!session()->has('anon_session')) session(['anon_session'=>Str::uuid()->toString()]);
        $attempt = LearningAttempt::create([
            'resource_id'=>$resource->id,
            'user_id'=>auth()->id(),
            'session_id'=>auth()->check()? null : session('anon_session'),
            'started_at'=>now(),'status'=>'started',
            'ip_address'=>$r->ip(),'user_agent'=>substr((string)$r->userAgent(),0,255),
        ]);
        return redirect()->route('resources.show', $resource->slug)->with('active_attempt', $attempt->id);
    }

    public function submit(Resource $resource, Request $r, LearningScoringService $scoring) {
        abort_unless(in_array($resource->type,['listening','reading']), 404);
        $attempt = LearningAttempt::findOrFail($r->integer('attempt_id'));
        abort_unless($attempt->resource_id === $resource->id, 403);
        // ownership check
        if ($attempt->user_id) abort_unless($attempt->user_id === auth()->id(), 403);
        else abort_unless($attempt->session_id === session('anon_session'), 403);
        // save answers
        $answers = $r->input('answers', []); // [questionId => optionId]
        foreach ($answers as $qid => $oid) {
            LearningAnswer::updateOrCreate(
                ['learning_attempt_id'=>$attempt->id,'question_id'=>(int)$qid],
                ['question_option_id'=>(int)$oid,'answered_at'=>now()]
            );
        }
        $scoring->score($attempt->fresh());
        return redirect()->route('resources.result', ['resource'=>$resource->slug,'attempt'=>$attempt->id]);
    }

    public function result(Resource $resource, LearningAttempt $attempt) {
        abort_unless($attempt->resource_id === $resource->id, 404);
        if ($attempt->user_id) abort_unless($attempt->user_id === auth()->id(), 403);
        else abort_unless($attempt->session_id === session('anon_session'), 403);
        $attempt->load(['answers.question.options','answers.option']);
        if ($resource->type === 'listening') $resource->load('listeningResource');
        if ($resource->type === 'reading') $resource->load('readingResource');
        return view('pages.resources.types.result', compact('resource','attempt'));
    }

    // Writing submissions
    public function submitWriting(Resource $resource, Request $r) {
        abort_unless($resource->type === 'writing', 404);
        $wr = $resource->writingResource;
        abort_unless($wr, 404);
        $data = $r->validate(['content'=>'required|string|max:20000','status'=>'nullable|in:draft,submitted']);
        $words = str_word_count(strip_tags($data['content']));
        $sub = WritingSubmission::create([
            'writing_resource_id'=>$wr->id,
            'user_id'=>auth()->id(),
            'content'=>$data['content'],
            'word_count'=>$words,
            'status'=>$data['status'] ?? 'submitted',
            'submitted_at'=> ($data['status'] ?? 'submitted') === 'submitted' ? now() : null,
        ]);
        return redirect()->route('resources.show', $resource->slug)->with('writing_submission_id', $sub->id);
    }
}
