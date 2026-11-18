<?php

namespace Tests\Feature;

use App\Logging\RedactSensitiveLogData;
use App\Logging\SensitiveDataRedactor;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DevelopmentUsersSeeder;
use Database\Seeders\InteractiveResourcesSeeder;
use Database\Seeders\ProductionAdminSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_accounts_remain_verified_and_deterministic(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::query()->where('email', 'admin@engleza.wiki')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(password_verify('password', $admin->password));
    }

    public function test_production_admin_seeder_refuses_to_run_outside_production(): void
    {
        $this->app['env'] = 'testing';

        try {
            Artisan::call('db:seed', [
                '--class' => ProductionAdminSeeder::class,
                '--force' => true,
            ]);
            $this->fail('Production administrator bootstrap must be restricted to production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('available only in production', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_seeding_does_not_recreate_demo_accounts_or_sample_content(): void
    {
        $admin = User::factory()->create([
            'name' => 'Existing owner',
            'email' => 'owner@example.com',
            'role' => 'admin',
        ]);
        $originalPasswordHash = $admin->password;
        $this->app['env'] = 'production';

        $this->runProductionSeeder();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame($originalPasswordHash, $admin->fresh()->password);
        $this->assertDatabaseMissing('users', ['email' => 'admin@engleza.wiki']);
        $this->assertDatabaseMissing('users', ['email' => 'editor@engleza.wiki']);
        $this->assertDatabaseMissing('resources', ['slug' => 'understanding-the-present-simple']);
    }

    public function test_development_only_seeders_refuse_direct_production_invocation(): void
    {
        $this->app['env'] = 'production';

        foreach ([DevelopmentUsersSeeder::class, InteractiveResourcesSeeder::class] as $seeder) {
            try {
                Artisan::call('db:seed', [
                    '--class' => $seeder,
                    '--force' => true,
                ]);
                $this->fail("{$seeder} must refuse to run in production.");
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('disabled in production', $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseMissing('resources', ['slug' => 'understanding-the-present-simple']);
    }

    public function test_production_seeding_requires_explicit_bootstrap_credentials_when_no_admin_exists(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.bootstrap_admin', ['name' => null, 'email' => null, 'password' => null]);

        try {
            $this->runProductionSeeder();
            $this->fail('Production seeding should refuse to proceed without an administrator or explicit bootstrap credentials.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Set ADMIN_NAME, ADMIN_EMAIL', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_production_seeding_can_create_only_an_explicitly_configured_admin(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.bootstrap_admin', [
            'name' => 'Production Owner',
            'email' => 'owner@example.com',
            'password' => 'a-unique-production-password',
        ]);

        $this->runProductionSeeder();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'name' => 'Production Owner',
            'email' => 'owner@example.com',
            'role' => 'admin',
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'admin@engleza.wiki']);
        $this->assertDatabaseMissing('resources', ['slug' => 'understanding-the-present-simple']);
        $this->assertNotSame('a-unique-production-password', User::query()->firstOrFail()->password);
        $this->assertNotNull(User::query()->firstOrFail()->email_verified_at);
    }

    public function test_production_admin_bootstrap_rejects_short_passwords_and_existing_non_admin_emails(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.bootstrap_admin', [
            'name' => 'Production Owner',
            'email' => 'owner@example.com',
            'password' => 'short',
        ]);

        try {
            $this->runProductionSeeder();
            $this->fail('A short production bootstrap password must be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('at least 16 characters', $exception->getMessage());
        }

        User::factory()->create(['email' => 'owner@example.com', 'role' => 'learner']);
        Config::set('app.bootstrap_admin.password', 'a-unique-production-password');

        try {
            $this->runProductionSeeder();
            $this->fail('A learner account must not be silently promoted by the bootstrap seeder.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already belongs to a non-admin account', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 1);
    }

    public function test_web_middleware_keeps_csrf_protection_enabled(): void
    {
        $webMiddleware = app('router')->getMiddlewareGroups()['web'];

        $this->assertContains(PreventRequestForgery::class, $webMiddleware);
    }

    public function test_forwarded_headers_are_ignored_when_no_proxy_is_trusted(): void
    {
        TrustProxies::flushState();

        $request = Request::create('http://origin.example.test/up', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.20',
            'HTTP_X_FORWARDED_HOST' => 'attacker.example',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        app(TrustProxies::class)->handle($request, fn () => new Response);

        $this->assertSame('203.0.113.10', $request->ip());
        $this->assertSame('origin.example.test', $request->getHost());
        $this->assertFalse($request->isSecure());

        TrustProxies::flushState();
    }

    public function test_production_error_responses_do_not_expose_exception_messages_or_traces(): void
    {
        Config::set('app.debug', false);
        $this->app['env'] = 'production';

        Route::get('/_production-error-check', function () {
            throw new RuntimeException('sensitive-debug-marker');
        });

        $response = $this->get('/_production-error-check');

        $response->assertStatus(500);
        $response->assertDontSee('sensitive-debug-marker');
        $response->assertDontSee('ProductionConfigurationTest.php');
    }

    public function test_production_readiness_command_passes_with_the_documented_hostinger_configuration(): void
    {
        $this->app['env'] = 'production';
        Config::set('app.debug', false);
        Config::set('app.url', 'https://engleza.wiki');
        Config::set('app.timezone', 'UTC');
        Config::set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        Config::set('database.default', 'mysql');
        Config::set('session.driver', 'database');
        Config::set('session.encrypt', true);
        Config::set('session.secure', true);
        Config::set('session.http_only', true);
        Config::set('session.same_site', 'lax');
        Config::set('session.lifetime', 120);
        Config::set('cache.default', 'database');
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.test');
        Config::set('mail.mailers.smtp.port', 587);
        Config::set('mail.mailers.smtp.username', 'smtp-user');
        Config::set('mail.mailers.smtp.password', 'smtp-test-password');
        Config::set('mail.from.address', 'no-reply@example.test');
        Config::set('logging.default', 'stack');
        Config::set('app.bootstrap_admin.password', null);
        User::factory()->create(['email' => 'owner@example.com', 'role' => 'admin']);

        $exitCode = Artisan::call('app:production-readiness');
        $output = Artisan::output();

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Production readiness checks passed.', $output);
    }

    public function test_production_readiness_command_fails_closed_for_unsafe_configuration(): void
    {
        $this->app['env'] = 'testing';
        Config::set('app.debug', true);
        Config::set('app.url', 'http://localhost');
        Config::set('session.encrypt', false);
        Config::set('session.secure', false);
        Config::set('session.http_only', false);
        Config::set('session.same_site', 'none');
        Config::set('mail.default', 'log');
        Config::set('logging.channels.single.tap', []);
        Config::set('app.bootstrap_admin.password', 'temporary-secret-value');

        $exitCode = Artisan::call('app:production-readiness');
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('APP_ENV is production', $output);
        $this->assertStringContainsString('APP_DEBUG is disabled', $output);
        $this->assertStringContainsString('Session payloads are encrypted at rest', $output);
        $this->assertStringContainsString('Session cookies are Secure', $output);
        $this->assertStringContainsString('Database-backed session storage and its table are available', $output);
        $this->assertStringContainsString('Authenticated SMTP transport and sender are configured', $output);
        $this->assertStringContainsString('Default log channel redacts sensitive fields', $output);
        $this->assertStringNotContainsString('temporary-secret-value', $output);
    }

    public function test_configured_log_channel_redacts_secrets_before_placeholder_interpolation(): void
    {
        $path = storage_path('logs/production-redaction-test.log');
        Config::set('logging.channels.redaction_test', [
            'driver' => 'single',
            'tap' => [RedactSensitiveLogData::class],
            'path' => $path,
            'level' => 'debug',
            'replace_placeholders' => true,
        ]);

        Config::set('logging.channels.redaction_stack', [
            'driver' => 'stack',
            'channels' => ['redaction_test'],
            'ignore_exceptions' => false,
        ]);

        try {
            Log::channel('redaction_stack')->info('Authentication failed for password={password}', [
                'password' => 'never-write-this-password',
            ]);

            $contents = file_get_contents($path);

            $this->assertIsString($contents);
            $this->assertStringContainsString('[REDACTED]', $contents);
            $this->assertStringNotContainsString('never-write-this-password', $contents);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_sensitive_context_and_labelled_message_values_are_redacted_before_logging(): void
    {
        $record = new LogRecord(
            new \DateTimeImmutable,
            'testing',
            Level::Info,
            'Login failed password=plain-secret Authorization: Bearer bearer-secret',
            [
                'password' => 'plain-secret',
                'access_token' => 'token-secret',
                'metadata' => ['api_key' => 'nested-secret', 'user_id' => 42],
            ],
        );

        $redacted = (new SensitiveDataRedactor)($record);

        $this->assertStringNotContainsString('plain-secret', $redacted->message);
        $this->assertStringNotContainsString('bearer-secret', $redacted->message);
        $this->assertSame('[REDACTED]', $redacted->context['password']);
        $this->assertSame('[REDACTED]', $redacted->context['access_token']);
        $this->assertSame('[REDACTED]', $redacted->context['metadata']['api_key']);
        $this->assertSame(42, $redacted->context['metadata']['user_id']);
    }

    public function test_environment_and_private_key_files_are_ignored_without_ignoring_test_fixtures(): void
    {
        $ignoreRules = file_get_contents(base_path('.gitignore'));

        $this->assertIsString($ignoreRules);
        $this->assertStringContainsString('.env.*', $ignoreRules);
        $this->assertStringContainsString('!.env.example', $ignoreRules);
        $this->assertStringContainsString('!.env.testing', $ignoreRules);
        $this->assertStringContainsString('*.pem', $ignoreRules);
        $this->assertStringContainsString('*.p12', $ignoreRules);
        $this->assertStringContainsString('*.pfx', $ignoreRules);
    }

    public function test_example_environment_keeps_debug_and_secrets_safe_by_default(): void
    {
        $environment = file_get_contents(base_path('.env.example'));

        $this->assertIsString($environment);
        $this->assertStringContainsString("APP_DEBUG=false\n", $environment);
        $this->assertStringContainsString("APP_KEY=\n", $environment);
        $this->assertStringNotContainsString('TRUSTED_PROXIES=*', $environment);
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertIsString($bootstrap);
        $this->assertStringNotContainsString("trustProxies(at: '*')", $bootstrap);
    }

    private function runProductionSeeder(): void
    {
        Artisan::call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ]);
    }
}
