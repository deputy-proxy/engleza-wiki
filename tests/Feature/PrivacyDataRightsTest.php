<?php

namespace Tests\Feature;

use App\Filament\Resources\AssessmentResults\AssessmentResultResource;
use App\Filament\Resources\ClassGroups\ClassGroupResource;
use App\Filament\Resources\LearningAttempts\LearningAttemptResource;
use App\Filament\Resources\TestAttempts\TestAttemptResource;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\WritingSubmissions\WritingSubmissionResource;
use App\Models\PrivacyDataRequest;
use App\Models\User;
use App\Services\Privacy\PersonalDataExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PrivacyDataRightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_pages_are_visible_as_drafts_and_excluded_from_indexing(): void
    {
        foreach (['/privacy-policy', '/terms', '/cookie-policy'] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('NU ESTE ÎNCĂ APROBAT JURIDIC')
                ->assertSee('noindex,nofollow', false);
        }
    }

    public function test_approved_policy_configuration_removes_draft_notice_and_allows_indexing(): void
    {
        config(['privacy.policy_status' => 'published']);

        $this->get('/privacy-policy')
            ->assertOk()
            ->assertDontSee('NU ESTE ÎNCĂ APROBAT JURIDIC')
            ->assertSee('index,follow', false);
    }

    public function test_privacy_request_pages_require_a_verified_account(): void
    {
        $this->get('/privacy/requests')->assertRedirect('/login');

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get('/privacy/requests')
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_submit_and_track_a_deletion_request(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-password-123')]);

        $this->actingAs($user)->post('/privacy/requests', [
            'request_type' => 'deletion',
            'description' => 'Please close my account and review my retained records.',
            'current_password' => 'current-password-123',
        ])->assertRedirect(route('privacy.requests'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('privacy_data_requests', [
            'user_id' => $user->id,
            'request_type' => 'deletion',
            'status' => 'submitted',
        ]);

        $this->actingAs($user)->get('/privacy/requests')
            ->assertOk()
            ->assertSee('Please close my account and review my retained records.');
    }

    public function test_privacy_requests_require_current_password_and_description_for_changes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/privacy/requests', [
            'request_type' => 'deletion',
            'description' => 'Delete my account.',
            'current_password' => 'wrong-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertDatabaseCount('privacy_data_requests', 0);

        $this->actingAs($user)->post('/privacy/requests', [
            'request_type' => 'correction',
            'current_password' => 'password',
        ])->assertSessionHasErrors('description');
    }

    public function test_access_request_downloads_json_export_and_records_completion(): void
    {
        $user = User::factory()->create(['password' => Hash::make('current-password-123')]);

        $response = $this->actingAs($user)->post('/privacy/requests', [
            'request_type' => 'access',
            'current_password' => 'current-password-123',
        ]);

        $response->assertOk()
            ->assertDownload('engleza-wiki-personal-data.json')
            ->assertHeader('content-type', 'application/json; charset=UTF-8');

        $this->assertDatabaseHas('privacy_data_requests', [
            'user_id' => $user->id,
            'request_type' => 'access',
            'status' => 'completed',
        ]);

        $export = app(PersonalDataExport::class)->for($user);
        $this->assertSame($user->email, $export['account']['email']);
        $this->assertArrayNotHasKey('password', $export['account']);
        $this->assertArrayNotHasKey('remember_token', $export['account']);
        $this->assertArrayHasKey('test_attempts', $export['records']);
        $this->assertArrayHasKey('writing_submissions', $export['records']);
        $this->assertArrayHasKey('privacy_data_requests', $export['records']);
    }

    public function test_personal_data_export_is_scoped_to_the_requesting_account(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        foreach ([$owner, $other] as $user) {
            PrivacyDataRequest::create([
                'user_id' => $user->id,
                'request_type' => 'correction',
                'status' => 'submitted',
                'description' => 'Request belonging to '.$user->email,
                'identity_verified_at' => now(),
            ]);
        }

        $export = app(PersonalDataExport::class)->for($owner);

        $this->assertCount(1, $export['records']['privacy_data_requests']);
        $this->assertSame('Request belonging to '.$owner->email, $export['records']['privacy_data_requests'][0]['description']);
        $this->assertStringNotContainsString($other->email, json_encode($export));
    }

    public function test_user_cannot_read_another_users_privacy_request_or_internal_notes(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        PrivacyDataRequest::create([
            'user_id' => $owner->id,
            'request_type' => 'correction',
            'status' => 'in_review',
            'description' => 'Private request details.',
            'admin_notes' => 'Staff-only review notes.',
            'identity_verified_at' => now(),
        ]);

        $this->actingAs($other)->get('/privacy/requests')
            ->assertOk()
            ->assertDontSee('Private request details.')
            ->assertDontSee('Staff-only review notes.');
    }

    public function test_only_administrators_can_process_privacy_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $editor = User::factory()->create(['role' => 'editor']);
        $owner = User::factory()->create();
        $request = PrivacyDataRequest::create([
            'user_id' => $owner->id,
            'request_type' => 'deletion',
            'status' => 'submitted',
            'description' => 'Close account.',
            'identity_verified_at' => now(),
        ]);

        $this->actingAs($editor)->get('/admin/privacy/requests')->assertForbidden();
        $this->actingAs($admin)->get('/admin/privacy/requests')->assertOk();

        $this->actingAs($admin)->put(route('admin.privacy.requests.update', $request), [
            'status' => 'in_review',
            'admin_notes' => 'Checking retention and class links.',
        ])->assertRedirect();

        $this->assertDatabaseHas('privacy_data_requests', [
            'id' => $request->id,
            'status' => 'in_review',
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Checking retention and class links.',
        ]);

        $this->actingAs($owner)->get('/privacy/requests')
            ->assertOk()
            ->assertDontSee('Checking retention and class links.');
    }

    public function test_editor_cannot_open_learner_submission_admin_page(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($editor)->get('/admin/writing-submissions')->assertForbidden();
        $this->actingAs($admin)->get('/admin/writing-submissions')->assertOk();
    }

    public function test_pending_privacy_request_prevents_account_deletion(): void
    {
        $user = User::factory()->create();
        PrivacyDataRequest::create([
            'user_id' => $user->id,
            'request_type' => 'deletion',
            'status' => 'submitted',
            'description' => 'Please close this account.',
            'identity_verified_at' => now(),
        ]);

        try {
            $user->delete();
            $this->fail('A user with a retained privacy request must not be deleted by cascade.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
    }

    public function test_cookie_preferences_can_be_saved_and_withdrawn(): void
    {
        $accepted = $this->post('/privacy/cookie-settings', ['analytics' => '1'])
            ->assertRedirect()
            ->assertCookie('engleza_cookie_preferences');

        $acceptedCookie = collect($accepted->headers->getCookies())
            ->first(fn ($cookie): bool => $cookie->getName() === 'engleza_cookie_preferences');

        $this->assertNotNull($acceptedCookie);
        $this->assertTrue($acceptedCookie->isHttpOnly());
        $this->assertSame('lax', strtolower($acceptedCookie->getSameSite() ?? ''));

        $this->post('/privacy/cookie-settings', ['analytics' => '0'])
            ->assertRedirect()
            ->assertCookie('engleza_cookie_preferences');
    }

    public function test_learner_records_in_filament_are_admin_only(): void
    {
        $editor = User::factory()->create(['role' => 'editor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($editor);
        foreach ([
            AssessmentResultResource::class,
            ClassGroupResource::class,
            LearningAttemptResource::class,
            TestAttemptResource::class,
            UserResource::class,
            WritingSubmissionResource::class,
        ] as $resource) {
            $this->assertFalse($resource::canViewAny(), $resource);
        }

        $this->actingAs($admin);
        foreach ([
            AssessmentResultResource::class,
            ClassGroupResource::class,
            LearningAttemptResource::class,
            TestAttemptResource::class,
            UserResource::class,
            WritingSubmissionResource::class,
        ] as $resource) {
            $this->assertTrue($resource::canViewAny(), $resource);
        }
    }
}
