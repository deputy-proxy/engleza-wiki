<?php

namespace App\Http\Controllers;

use App\Models\ClassGroup;
use App\Models\Resource;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\Test\TestAttemptService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestController extends Controller
{
    public function index()
    {
        $tests = Test::published()->where('is_public', true)->with(['translations', 'level.translations', 'skill.translations'])->latest('published_at')->get();

        return view('pages.tests.index', compact('tests'));
    }

    public function show(Test $test)
    {
        abort_unless($test->status === 'published' && $test->is_public, 404);
        $test->load(['translations', 'level.translations', 'skill.translations', 'sections.translations']);
        $test->loadCount('sections');
        $questionCount = $test->allQuestions()->count();

        return view('pages.tests.show', compact('test', 'questionCount'));
    }

    public function start(Test $test, Request $r, TestAttemptService $attempts)
    {
        $attempts->authorizeStart($test, auth()->id());
        if (! auth()->check() && ! session()->has('anon_session')) {
            session(['anon_session' => Str::uuid()->toString()]);
        }

        // Optional class-code binding
        $classGroupId = null;
        if ($code = $r->input('class_code')) {
            $g = ClassGroup::where('join_code', strtoupper($code))->where('is_active', true)->first();
            if ($g && ($g->test_id === null || $g->test_id === $test->id)) {
                $classGroupId = $g->id;
            }
        }

        $attempt = $attempts->start($test, [
            'user_id' => auth()->id(),
            'session_id' => auth()->check() ? null : session('anon_session'),
            'class_group_id' => $classGroupId,
            'started_at' => now(),
            'status' => 'started',
        ]);

        return redirect()->route('tests.take', ['test' => $test->slug, 'attempt' => $attempt->id]);
    }

    public function take(Test $test, TestAttempt $attempt)
    {
        abort_unless($attempt->test_id === $test->id, 404);
        $this->authorizeAttempt($attempt);
        if (in_array($attempt->status, ['completed', 'expired'], true)) {
            return redirect()->route('tests.result', ['test' => $test->slug, 'attempt' => $attempt->id]);
        }
        abort_unless($attempt->status === 'started', 410);

        return view('pages.tests.take', compact('test', 'attempt'));
    }

    public function result(Test $test, TestAttempt $attempt, TestAttemptService $attempts)
    {
        abort_unless($attempt->test_id === $test->id, 404);
        $this->authorizeAttempt($attempt);
        abort_unless(in_array($attempt->status, ['completed', 'expired'], true), 404);
        $attempt->load(['result', 'test.translations', 'test.level.translations', 'answers.question.translations', 'answers.question.options', 'answers.option']);
        $questions = $attempts->questionPayload($attempt, revealAnswers: true);
        $recIds = [];
        if ($attempt->result && $attempt->result->recommendation) {
            $data = json_decode($attempt->result->recommendation, true) ?: [];
            $recIds = $data['resource_ids'] ?? [];
        }
        $recommendations = Resource::published()->with(['translations', 'level.translations'])->whereIn('id', $recIds)->get();

        return view('pages.tests.result', compact('test', 'attempt', 'recommendations', 'questions'));
    }

    private function authorizeAttempt(TestAttempt $attempt): void
    {
        app(TestAttemptService::class)->authorize($attempt, auth()->id(), session('anon_session'));
    }
}
