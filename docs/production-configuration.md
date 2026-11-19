# Production configuration and operations

> This document describes the intended production configuration. It does not prove the live environment currently matches it. Use the [production operations runbook](operations-runbook.md) for the latest observed hosting state, smoke-test evidence, recovery procedures and outstanding verification.

This checklist describes the production deployment of `engleza.wiki` on Hostinger web hosting. It is intentionally specific to the verified hosting topology; do not copy proxy settings from a generic Laravel example.

## Verified hosting topology

The Hostinger account currently reports:

- Website: `engleza.wiki`, hosted on Hostinger shared/CloudLinux web hosting.
- Hostinger SSL certificate: active.
- Hostinger HTTP-to-HTTPS redirect: enabled.
- Hostinger CDN: enabled.
- Hostinger server-side cache: disabled.
- Live HTTPS responses are served by `hcdn` and include `x-hcdn-request-id`.
- The live Laravel response sets both the session cookie and XSRF cookie with `Secure`; the session cookie also has `HttpOnly` and `SameSite=Lax`. The HTTP response does not reveal Laravel's internal `SESSION_DRIVER` or `CACHE_STORE`; the production readiness command checks those values and their database tables from inside the application.

Hostinger documents that its web-hosting server trusts its own CDN and applies the forwarded client address before PHP receives the request. On this deployment, Laravel therefore does **not** need to trust arbitrary `X-Forwarded-*` headers. The previous `trustProxies(at: '*')` configuration was removed. Trusting every connecting IP would allow clients that reach the origin directly to spoof forwarded headers.

References:

- [Hostinger CDN: visitor IP addresses in logs and analytics](https://www.hostinger.com/support/hostinger-cdn-visitor-ip-addresses-in-logs-and-analytics/)
- [Hostinger CDN and DNS records](https://www.hostinger.com/support/hostinger-cdn-and-your-dns-records/)

Do not reintroduce wildcard proxy trust. If the hosting topology changes, obtain the exact trusted proxy IP ranges from the hosting provider, ensure direct-origin requests cannot bypass those proxies, and configure only those addresses. Never trust the first arbitrary `X-Forwarded-For` value.

## Environment variables

Set these values in the private production `.env` file or hosting environment, not in Git:

```dotenv
APP_NAME=engleza.wiki
APP_ENV=production
APP_DEBUG=false
APP_URL=https://engleza.wiki
APP_TIMEZONE=UTC
APP_KEY=base64:<unique-generated-key>

DB_CONNECTION=mysql
DB_HOST=<Hostinger database host>
DB_PORT=3306
DB_DATABASE=<production database>
DB_USERNAME=<dedicated database user>
DB_PASSWORD=<strong database password>

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_EXPIRE_ON_CLOSE=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SESSION_PATH=/
SESSION_DOMAIN=null

CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

MAIL_MAILER=smtp
MAIL_HOST=<authenticated SMTP host>
MAIL_PORT=587
MAIL_USERNAME=<SMTP username>
MAIL_PASSWORD=<SMTP password>
MAIL_SCHEME=smtp
MAIL_FROM_ADDRESS=<verified sender address>
MAIL_FROM_NAME="${APP_NAME}"
```

Notes:

- Generate `APP_KEY` once for this environment with `php artisan key:generate`. Preserve it across deployments and backups. Rotating it invalidates encrypted application data and active sessions.
- Use a dedicated database and database user with only the privileges required by the application. Keep the production database credentials outside source control.
- `SESSION_DRIVER=database` and `CACHE_STORE=database` use the application's MySQL database. Confirm the `sessions` and `cache` tables exist after migrations.
- Set `SESSION_SECURE_COOKIE=true` in production. `HttpOnly` and `SameSite=Lax` should remain enabled. Keep `SESSION_DOMAIN` unset/null unless there is a documented cross-subdomain authentication requirement.
- `SESSION_ENCRYPT=true` encrypts session payloads stored by Laravel. Enabling it invalidates sessions created before the setting was enabled; schedule this as a one-time sign-out.
- The repository's sample `.env.example` keeps `SESSION_SECURE_COOKIE=null` so local HTTP development continues to work. Production must explicitly set it to `true`.
- Keep the Laravel web middleware's CSRF protection enabled. Do not exclude authentication, account, or state-changing routes from CSRF verification.
- Use an authenticated production mail transport. The `log` mailer is for local development only and does not deliver verification or password-reset messages.
- Use daily logs with a bounded retention period. A Monolog processor redacts common sensitive context keys (including passwords, tokens, API keys, cookies and authorization data) and labelled secret values in messages before placeholder interpolation. This is a safety net, not permission to log secrets: never put credentials or unlabelled secret values into free-form messages. Log identifiers and outcomes instead.

## Administrator bootstrap and database seeding

`DatabaseSeeder` has different behavior by environment:

- `local` and `testing`: creates the documented development accounts and sample educational content. The demo accounts all use the password `password` and must never be used in production. The dedicated development-user and interactive-sample seeders also refuse direct invocation in production.
- `production`: runs only `ProductionAdminSeeder`, then exits. It does not create demo accounts or re-seed sample content. The production admin seeder refuses to run outside production. Manage live curriculum content through the admin interface or a separately reviewed import, not the development sample seeders.

For a new production database with no administrator:

1. Back up the database and deploy the application code.
2. Temporarily set `ADMIN_NAME`, `ADMIN_EMAIL`, and `ADMIN_PASSWORD` in the private hosting environment. Use a unique password of at least 16 characters; do not use `password` or any development credential.
3. Clear any existing configuration cache so the temporary variables are loaded: `php artisan config:clear`.
4. Run `php artisan db:seed --class=Database\Seeders\DatabaseSeeder --force`.
5. Confirm the configured account can sign in and access `/admin`.
6. Remove `ADMIN_PASSWORD` (and the other temporary bootstrap variables) from the hosting environment, then rebuild the configuration cache with `php artisan config:cache`. Do not leave the bootstrap password in a cached config file.
7. Run `php artisan app:production-readiness` and resolve every failed check.

The bootstrap seeder creates an administrator only when no administrator exists. It never changes an existing administrator's password. It refuses to promote an existing non-admin account implicitly. Promote an existing account through the authorized admin workflow instead.

The production seeder deliberately does not delete existing demo accounts because deleting accounts can destroy user history. Before relying on this protection in an already-seeded production database, review the following addresses in the users table and disable/delete them manually if they are still the original demo accounts:

- `admin@engleza.wiki`
- `editor@engleza.wiki`
- `teacher@engleza.wiki`
- `learner@engleza.wiki`

Do not delete an account until its ownership and related records have been checked. The production readiness command fails while any of these known development accounts remain.

## Deployment and post-deployment checks

For each production release:

1. Confirm the deployment environment still has `APP_ENV=production`, `APP_DEBUG=false`, the correct HTTPS `APP_URL`, and the existing `APP_KEY`.
2. Back up the database before migrations.
3. Run `php artisan migrate --force`.
4. Run `php artisan optimize` after the environment values are correct.
5. Run `php artisan app:production-readiness`.
6. Confirm `https://engleza.wiki/up` responds successfully and the public site, login, email verification, password recovery, and `/admin` work over HTTPS.
7. Check the Hostinger CDN response header `x-hcdn-request-id` and confirm session cookies remain `Secure`, `HttpOnly`, and `SameSite=Lax`.

The readiness command checks, without printing secret values:

- Production environment and disabled debug mode.
- HTTPS application URL, valid timezone, and a correctly sized encryption key.
- MySQL/MariaDB connectivity.
- Database-backed session storage and the `sessions` table.
- Encrypted session payloads and Secure, HttpOnly, SameSite session-cookie settings.
- Database-backed cache storage and the `cache` table.
- Authenticated SMTP transport and sender address.
- Enabled application logging with the sensitive-data redaction processor on the default channel.
- An existing administrator, absence of the four known development accounts, and removal of temporary bootstrap credentials.

The command is a configuration smoke check, not a substitute for a backup restore test, mail-delivery test, or review of the live hosting environment. It intentionally reports only pass/fail labels and never prints environment values.

## Secrets and repository hygiene

- `.env` and all `.env.*` variants are ignored by Git, except the intentionally committed `.env.example` and `.env.testing` fixtures. Private key and certificate file extensions (`.pem`, `.p12`, `.pfx`) are also ignored.
- `.env.example` contains placeholders only; `APP_KEY` and all production credentials are blank or unset.
- `.env.testing` contains test-only fixtures and must never be copied to production.
- Never commit private keys, access tokens, database dumps, production environment files, or real user credentials.
- Keep sensitive values out of logs, exception messages, command output, screenshots, and issue comments.
