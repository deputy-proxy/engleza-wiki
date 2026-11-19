<?php

namespace App\Console\Commands;

use App\Logging\RedactSensitiveLogData;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ProductionReadiness extends Command
{
    protected $signature = 'app:production-readiness';

    protected $description = 'Check production configuration, persistence, security and administrator bootstrap.';

    /** @var array<int, string> */
    private array $failures = [];

    public function handle(): int
    {
        $this->check('APP_ENV is production', app()->environment('production'));
        $this->check('APP_DEBUG is disabled', config('app.debug') === false);
        $this->check('APP_URL uses HTTPS', parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https');
        $this->check('APP_TIMEZONE is a valid IANA timezone', in_array(config('app.timezone'), timezone_identifiers_list(), true));
        $this->check('APP_KEY is configured for the selected cipher', $this->hasValidEncryptionKey());

        $databaseDriver = (string) config('database.default');
        $this->check('Database driver is MySQL or MariaDB', in_array($databaseDriver, ['mysql', 'mariadb'], true));
        $databaseReachable = $this->databaseIsReachable();
        $this->check('Database connection is reachable', $databaseReachable);

        $sessionReady = config('session.driver') === 'database'
            && $databaseReachable
            && $this->tableExists((string) config('session.table', 'sessions'));
        $this->check('Database-backed session storage and its table are available', $sessionReady);
        $this->check('Session payloads are encrypted at rest', config('session.encrypt') === true);
        $this->check('Session cookies are Secure', config('session.secure') === true);
        $this->check('Session cookies are HttpOnly', config('session.http_only') === true);
        $this->check('Session SameSite policy is Lax or Strict', in_array(config('session.same_site'), ['lax', 'strict'], true));
        $this->check('Session lifetime is positive', (int) config('session.lifetime') > 0);

        $cacheReady = config('cache.default') === 'database'
            && $databaseReachable
            && $this->tableExists((string) config('cache.stores.database.table', 'cache'));
        $this->check('Database-backed cache store and its table are available', $cacheReady);

        $mailer = (string) config('mail.default');
        $mailConfig = config("mail.mailers.$mailer", []);
        $mailReady = $mailer === 'resend'
            && is_array($mailConfig)
            && ($mailConfig['transport'] ?? null) === 'resend'
            && filled(config('services.resend.key'))
            && filter_var((string) config('mail.from.address'), FILTER_VALIDATE_EMAIL) !== false;
        $this->check('Resend API transport, API key and sender are configured', $mailReady);

        $logChannel = (string) config('logging.default');
        $this->check('Application logging is enabled', ! in_array($logChannel, ['', 'null', 'emergency'], true));
        $this->check('Default log channel redacts sensitive fields', $this->logChannelRedactsSensitiveData($logChannel));
        $this->check('Temporary administrator bootstrap password is absent', blank(config('app.bootstrap_admin.password')));

        if ($databaseReachable) {
            try {
                $this->check('At least one administrator exists', User::query()->where('role', 'admin')->exists());
                $demoUsersExist = User::query()->whereIn('email', [
                    'admin@engleza.wiki',
                    'editor@engleza.wiki',
                    'teacher@engleza.wiki',
                    'learner@engleza.wiki',
                ])->exists();
                $this->check('Known-password development accounts are absent', ! $demoUsersExist);
            } catch (Throwable) {
                $this->check('Administrator and development-account audit is queryable', false);
            }
        } else {
            $this->check('Administrator exists and development accounts are absent', false);
        }

        if ($this->failures === []) {
            $this->info('Production readiness checks passed.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error(count($this->failures).' production readiness check(s) failed.');

        return self::FAILURE;
    }

    private function check(string $label, bool $passed): void
    {
        if ($passed) {
            $this->line('<fg=green>PASS</> '.$label);

            return;
        }

        $this->line('<fg=red>FAIL</> '.$label);
        $this->failures[] = $label;
    }

    private function logChannelRedactsSensitiveData(string $channel): bool
    {
        $channelConfig = config("logging.channels.$channel", []);

        if ($channel === 'stack') {
            $channels = $channelConfig['channels'] ?? [];

            if (! is_array($channels) || $channels === []) {
                return false;
            }

            foreach ($channels as $stackChannel) {
                if (! in_array(RedactSensitiveLogData::class, config("logging.channels.$stackChannel.tap", []), true)) {
                    return false;
                }
            }

            return true;
        }

        return in_array(RedactSensitiveLogData::class, $channelConfig['tap'] ?? [], true);
    }

    private function hasValidEncryptionKey(): bool
    {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }

        return $key !== '' && Encrypter::supported($key, (string) config('app.cipher'));
    }

    private function databaseIsReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (Throwable) {
            return false;
        }
    }
}
