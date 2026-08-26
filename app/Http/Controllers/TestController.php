<?php
namespace App\Http\Controllers;
use App\Models\{Test, TestAttempt, Resource};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TestController extends Controller {
    public function index() {
        $tests = Test::published()->where('is_public',true)->with(['level','skill'])->latest('published_at')->get();
        return view('pages.tests.index', compact('tests'));
    }
    public function show(Test $test) {
        abort_unless($test->status==='published' && $test->is_public, 404);
        $test->loadCount('sections');
        $questionCount = $test->allQuestions()->count();
        return view('pages.tests.show', compact('test','questionCount'));
    }
    public function start(Test $test, Request $r) {
        abort_unless($test->status==='published' && $test->is_public, 404);
        if (!session()->has('anon_session')) session(['anon_session'=>Str::uuid()->toString()]);
        $attempt = TestAttempt::create([
            'test_id'=>$test->id,
            'user_id'=>auth()->id(),
            'session_id'=>auth()->check() ? null : session('anon_session'),
            'started_at'=>now(),
            'status'=>'started',
            'ip_address'=>$r->ip(),'user_agent'=>substr((string)$r->userAgent(),0,255),
        ]);
        return redirect()->route('tests.take', ['test'=>$test->slug,'attempt'=>$attempt->id]);
    }
    public function take(Test $test, TestAttempt $attempt) {
        abort_unless($attempt->test_id === $test->id, 404);
        $this->authorizeAttempt($attempt);
        if ($attempt->status === 'completed') return redirect()->route('tests.result',['test'=>$test->slug,'attempt'=>$attempt->id]);
        return view('pages.tests.take', compact('test','attempt'));
    }
    public function result(Test $test, TestAttempt $attempt) {
        abort_unless($attempt->test_id === $test->id, 404);
        $this->authorizeAttempt($attempt);
        $attempt->load(['result','test','answers.question']);
        $recIds = [];
        if ($attempt->result && $attempt->result->recommendation) {
            $data = json_decode($attempt->result->recommendation, true) ?: [];
            $recIds = $data['resource_ids'] ?? [];
        }
        $recommendations = Resource::published()->whereIn('id',$recIds)->get();
        return view('pages.tests.result', compact('test','attempt','recommendations'));
    }
    private function authorizeAttempt(TestAttempt $attempt): void {
        if ($attempt->user_id) { abort_unless(auth()->id() === $attempt->user_id, 403); return; }
        abort_unless(session('anon_session') === $attempt->session_id, 403);
    }
}
