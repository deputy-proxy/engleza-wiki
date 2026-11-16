<?php

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Test\TestScoringService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_homepage_loads(): void
    {
        $this->get('/')->assertOk()->assertSee('engleza.wiki');
    }

    public function test_resource_index_and_show(): void
    {
        $this->get('/resources')->assertOk();
        $r = Resource::published()->first();
        $this->get('/resources/'.$r->slug)->assertOk()->assertSee($r->title);
    }

    public function test_draft_resource_returns_404(): void
    {
        $r = Resource::factory()->draft()->create();
        $this->get('/resources/'.$r->slug)->assertNotFound();
    }

    public function test_search_works(): void
    {
        $this->get('/search?q=past')->assertOk()->assertSee('past');
    }

    public function test_category_filter(): void
    {
        $this->get('/categories/grammar')->assertOk();
    }

    public function test_tests_index_and_show(): void
    {
        $this->get('/tests')->assertOk();
        $t = Test::published()->first();
        $this->get('/tests/'.$t->slug)->assertOk()->assertSee($t->title);
    }

    public function test_anonymous_user_can_start_test(): void
    {
        $t = Test::published()->first();
        $resp = $this->post('/tests/'.$t->slug.'/start');
        $resp->assertRedirect();
        $this->assertDatabaseHas('test_attempts', ['test_id' => $t->id, 'user_id' => null]);
    }

    public function test_registered_user_can_start_test(): void
    {
        $u = User::where('email', 'learner@engleza.wiki')->first();
        $t = Test::published()->first();
        $this->actingAs($u)->post('/tests/'.$t->slug.'/start')->assertRedirect();
        $this->assertDatabaseHas('test_attempts', ['test_id' => $t->id, 'user_id' => $u->id]);
    }

    public function test_scoring_service_calculates_correctly(): void
    {
        $t = Test::published()->first();
        $attempt = TestAttempt::create(['test_id' => $t->id, 'session_id' => 's', 'started_at' => now(), 'status' => 'started']);
        foreach ($t->allQuestions() as $q) {
            $correct = $q->options->firstWhere('is_correct', true);
            TestAnswer::create(['test_attempt_id' => $attempt->id, 'question_id' => $q->id, 'question_option_id' => $correct->id, 'answered_at' => now()]);
        }
        $result = app(TestScoringService::class)->score($attempt);
        $this->assertEquals(100.0, $result->overall_percentage);
        $this->assertNotNull($result->level);
    }

    public function test_admin_route_requires_auth(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_admin_login_page_loads(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_registration_creates_learner(): void
    {
        $this->post('/register', [
            'name' => 'New User', 'email' => 'newuser@test.com', 'password' => 'password12', 'password_confirmation' => 'password12',
        ])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com', 'role' => 'learner']);
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
