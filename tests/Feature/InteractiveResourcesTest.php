<?php
namespace Tests\Feature;

use App\Models\{Resource, User, ListeningResource, ReadingResource, WritingResource, Question, QuestionOption, LearningAttempt, LearningAnswer, WritingSubmission};
use App\Services\Learning\LearningScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractiveResourcesTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
    }

    public function test_card_resource_page_renders(): void {
        $r = Resource::where('type','card')->published()->first();
        $this->assertNotNull($r);
        $this->get('/resources/'.$r->slug)->assertOk()
            ->assertSee($r->title)
            ->assertSee('CARDS');
    }

    public function test_listening_resource_page_renders_with_questions(): void {
        $r = Resource::where('type','listening')->published()->first();
        $this->assertNotNull($r);
        $resp = $this->get('/resources/'.$r->slug);
        $resp->assertOk()->assertSee($r->title)->assertSee('LISTENING');
    }

    public function test_reading_resource_page_renders_with_passage(): void {
        $r = Resource::where('type','reading')->published()->first();
        $this->assertNotNull($r);
        $resp = $this->get('/resources/'.$r->slug);
        $resp->assertOk()->assertSee($r->title)->assertSee('READING');
    }

    public function test_writing_resource_page_renders_prompt(): void {
        $r = Resource::where('type','writing')->published()->first();
        $this->assertNotNull($r);
        $this->get('/resources/'.$r->slug)->assertOk()->assertSee('WRITING');
    }

    public function test_learning_scoring_computes_correct_percentage(): void {
        $r = Resource::where('type','reading')->published()->first();
        $attempt = LearningAttempt::create(['resource_id'=>$r->id,'session_id'=>'s','started_at'=>now(),'status'=>'started']);
        foreach ($r->readingResource->questions as $q) {
            $correct = $q->options->firstWhere('is_correct', true);
            LearningAnswer::create(['learning_attempt_id'=>$attempt->id,'question_id'=>$q->id,'question_option_id'=>$correct->id,'answered_at'=>now()]);
        }
        $result = app(LearningScoringService::class)->score($attempt->fresh());
        $this->assertEquals(100.0, $result->percentage);
    }

    public function test_authenticated_user_can_submit_writing(): void {
        $r = Resource::where('type','writing')->published()->first();
        $u = User::where('email','learner@engleza.wiki')->first();
        $this->actingAs($u)->post('/resources/'.$r->slug.'/writing/submit', [
            'content'=>str_repeat('This is my writing test. ', 20),
            'status'=>'submitted',
        ])->assertRedirect();
        $this->assertDatabaseHas('writing_submissions', ['user_id'=>$u->id,'status'=>'submitted']);
    }

    public function test_guest_cannot_submit_writing(): void {
        $r = Resource::where('type','writing')->published()->first();
        $this->post('/resources/'.$r->slug.'/writing/submit', ['content'=>'hi'])->assertRedirect('/login');
    }

    public function test_resource_index_type_filter_works(): void {
        $this->get('/resources?type=card')->assertOk()->assertSee('CARDS');
        $this->get('/resources?type=listening')->assertOk()->assertSee('LISTENING');
    }
}
