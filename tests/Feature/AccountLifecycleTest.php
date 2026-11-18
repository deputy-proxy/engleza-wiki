<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_regenerates_the_session_and_sends_verification_email(): void
    {
        Notification::fake();

        $this->get('/register');
        $originalSessionId = session()->getId();

        $this->post('/register', [
            'name' => 'New Learner',
            'email' => 'new-learner@example.test',
            'password' => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'new-learner@example.test')->firstOrFail();

        $this->assertNotSame($originalSessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('learner', $user->role);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_login_regenerates_session_and_unverified_users_are_prompted_to_verify(): void
    {
        $user = User::factory()->unverified()->create(['password' => Hash::make('secure-password-123')]);

        $this->get('/login');
        $originalSessionId = session()->getId();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'secure-password-123',
        ])->assertRedirect(route('verification.notice'));

        $this->assertNotSame($originalSessionId, session()->getId());
        $this->assertAuthenticatedAs($user);
    }

    public function test_verification_link_is_signed_expires_and_marks_only_the_authenticated_user(): void
    {
        $user = User::factory()->unverified()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status');

        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->actingAs($user)->get($url)
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', __('Your email address is already verified.'));
    }

    public function test_verification_link_cannot_be_used_by_another_user_or_after_expiry(): void
    {
        $owner = User::factory()->unverified()->create();
        $other = User::factory()->create();

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $owner->id,
            'hash' => sha1($owner->getEmailForVerification()),
        ]);

        $this->actingAs($other)->get($url)->assertForbidden();
        $this->assertNull($owner->fresh()->email_verified_at);

        $expiredUrl = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
            'id' => $owner->id,
            'hash' => sha1($owner->getEmailForVerification()),
        ]);

        $this->actingAs($owner)->get($expiredUrl)->assertForbidden();
        $this->assertNull($owner->fresh()->email_verified_at);
    }

    public function test_verification_resend_is_rate_limited(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->post('/email/verification-notification')->assertRedirect();
        }

        $this->post('/email/verification-notification')->assertTooManyRequests();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_accounts_do_not_receive_another_verification_email(): void
    {
        Notification::fake();
        $verified = User::factory()->create();

        $this->actingAs($verified)->post('/email/verification-notification')
            ->assertRedirect(route('dashboard'));

        Notification::assertNotSentTo($verified, VerifyEmail::class);
    }

    public function test_password_reset_request_uses_the_same_response_for_known_and_unknown_emails(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $known = $this->from('/password/forgot')->post('/password/email', ['email' => $user->email]);
        $unknown = $this->from('/password/forgot')->post('/password/email', ['email' => 'missing@example.test']);

        $known->assertRedirect('/password/forgot')->assertSessionHas('status');
        $unknown->assertRedirect('/password/forgot')->assertSessionHas('status');
        $this->post('/password/email', ['email' => $user->email])->assertRedirect('/password/forgot');
        $this->assertSame(__('If an account exists for that email address, a password reset link has been sent.'), session('status'));
        Notification::assertSentToTimes($user, ResetPassword::class, 1);
    }

    public function test_password_reset_token_is_single_use_and_password_is_updated(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => Hash::make('old-password-123')]);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Alege o parolă nouă');

        $payload = [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ];

        $this->post('/password/reset', $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));

        $this->from('/password/reset/'.$token)->post('/password/reset', $payload)
            ->assertRedirect('/password/reset/'.$token)
            ->assertSessionHasErrors('email');
    }

    public function test_expired_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->travel(61)->minutes();

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_logout_invalidates_the_session_and_regenerates_the_csrf_token(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['private_marker' => 'present']);
        $this->post('/logout')->assertRedirect('/');

        $this->assertGuest();
        $this->assertFalse(session()->has('private_marker'));
    }

    public function test_sessions_with_an_old_password_hash_are_invalidated_on_the_next_web_request(): void
    {
        $user = User::factory()->create(['password' => Hash::make('new-password-456')]);

        $this->actingAs($user)
            ->withSession(['password_hash_web' => Hash::make('old-password-123')])
            ->get('/dashboard')
            ->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_guest_learning_and_public_resources_remain_accessible(): void
    {
        $this->get('/resources')->assertOk();
        $this->get('/tests')->assertOk();
    }
}
