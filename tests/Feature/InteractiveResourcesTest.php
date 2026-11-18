<?php

namespace Tests\Feature;

use App\Livewire\Resources\InteractiveCards;
use App\Models\LearningAnswer;
use App\Models\LearningAttempt;
use App\Models\Resource;
use App\Models\User;
use App\Services\Learning\LearningScoringService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->approveSeededContent();
    }

    public function test_card_resource_page_renders(): void
    {
        $r = Resource::where('type', 'card')->published()->first();
        $this->assertNotNull($r);
        $this->get('/resources/'.$r->slug)->assertOk()
            ->assertSee($r->translated('title'))
            ->assertSee('CARDURI');
    }

    public function test_interactive_cards_component_can_reveal_and_navigate_cards(): void
    {
        $resource = Resource::where('type', 'card')->published()->firstOrFail();
        $cards = $resource->cardResource->cards()->orderBy('sort_order')->get();
        $firstCard = $cards->firstOrFail();

        Livewire::test(InteractiveCards::class, ['resource' => $resource])
            ->assertSee($firstCard->front)
            ->call('reveal')
            ->assertSee($firstCard->back)
            ->call('next')
            ->assertSee($cards->get(1)->front);
    }

    public function test_listening_resource_page_renders_with_questions(): void
    {
        $r = Resource::where('type', 'listening')->published()->first();
        $this->assertNotNull($r);
        $resp = $this->get('/resources/'.$r->slug);
        $resp->assertOk()->assertSee($r->translated('title'))->assertSee('ASCULTARE');
    }

    public function test_reading_resource_page_renders_with_passage(): void
    {
        $r = Resource::where('type', 'reading')->published()->first();
        $this->assertNotNull($r);
        $resp = $this->get('/resources/'.$r->slug);
        $resp->assertOk()->assertSee($r->translated('title'))->assertSee('LECTURĂ');
    }

    public function test_writing_resource_page_renders_prompt(): void
    {
        $r = Resource::where('type', 'writing')->published()->first();
        $this->assertNotNull($r);
        $this->get('/resources/'.$r->slug)->assertOk()->assertSee('SCRIERE');
    }

    public function test_learning_scoring_computes_correct_percentage(): void
    {
        $r = Resource::where('type', 'reading')->published()->first();
        $attempt = LearningAttempt::create(['resource_id' => $r->id, 'session_id' => 's', 'started_at' => now(), 'status' => 'started']);
        foreach ($r->readingResource->questions as $q) {
            $correct = $q->options->firstWhere('is_correct', true);
            LearningAnswer::create(['learning_attempt_id' => $attempt->id, 'question_id' => $q->id, 'question_option_id' => $correct->id, 'answered_at' => now()]);
        }
        $result = app(LearningScoringService::class)->score($attempt->fresh());
        $this->assertEquals(100.0, $result->percentage);
    }

    public function test_authenticated_user_can_submit_writing(): void
    {
        $r = Resource::where('type', 'writing')->published()->first();
        $u = User::where('email', 'learner@engleza.wiki')->first();
        $this->actingAs($u)->post('/resources/'.$r->slug.'/writing/submit', [
            'content' => str_repeat('This is my writing test. ', 20),
            'status' => 'submitted',
        ])->assertRedirect();
        $this->assertDatabaseHas('writing_submissions', ['user_id' => $u->id, 'status' => 'submitted']);
    }

    public function test_guest_cannot_submit_writing(): void
    {
        $r = Resource::where('type', 'writing')->published()->first();
        $this->post('/resources/'.$r->slug.'/writing/submit', ['content' => 'hi'])->assertRedirect('/login');
    }

    public function test_resource_index_type_filter_works(): void
    {
        $this->get('/resources?type=card')->assertOk()->assertSee('CARDURI');
        $this->get('/resources?type=listening')->assertOk()->assertSee('ASCULTARE');
    }
}
