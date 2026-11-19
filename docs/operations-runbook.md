# Production operations runbook

**Status: incomplete. Resend is selected and implemented in the application; live delivery and monitoring evidence remain outstanding.**
**Evidence snapshot:** 2026-11-19
**Environment:** `https://engleza.wiki`, hosted on Hostinger.

This document separates observations from assumptions. An item marked **Not verified** must not be represented as operationally ready. The live inspection found a production application error and a public exception page exposing database and stack-trace details. Resolve those incidents before treating this site as launch-ready.

## 1. Service inventory

| Area | Verified observation | Operational status |
|---|---|---|
| Hosting | Hostinger CloudLinux web hosting; Cloud Economy v3 order is active | Verified |
| PHP | PHP 8.3.36 | Verified |
| Database | A MySQL database is assigned to the domain; Hostinger reports approximately 7 MB used of a 6 GB quota | Connectivity and schema health not verified from the hosting API |
| TLS | Hostinger lifetime SSL certificate is active; HTTPS redirect is enabled | Verified |
| CDN | Hostinger CDN is enabled | Verified |
| Host cache | Server-side cache is disabled; cacheless/development mode is disabled | Verified |
| Public storage | Application uses Laravel's local public disk. The production `storage/app/public` directory contains only its placeholder; no `public/storage` entry was visible in the hosting file listing | Media backup and public-link behaviour not verified |
| Mail | Resend selected as the production transactional email provider; Laravel Resend transport and readiness checks are implemented in the repository. Existing DNS contains Hostinger MX/SPF/DKIM and DMARC `p=none` | Resend account, sender-domain authentication, API secret deployment and real delivery are not yet verified |
| Queue and scheduler | No application job classes, queue dispatches or scheduled tasks were found. Laravel `schedule:list` reports no scheduled tasks. No Hostinger cron job is configured for this website | No worker or scheduler is currently required by the inspected application code |
| Logs | A production `storage/logs/laravel.log` file exists (about 4.66 MB at inspection). No dated Laravel log files were visible | Rotation, retention, access controls and backup coverage not verified |
| Monitoring | The Laravel `/up` endpoint responds successfully | External uptime monitoring, database monitoring and alert routing not verified |

Do not copy credentials, database names, database hosts, account identifiers, or production environment values into this runbook or an issue comment.

## 2. Immediate production incidents

### 2.1 Public routes return database errors

Live HTTPS smoke checks on 2026-11-19 returned:

| Route | HTTP status |
|---|---:|
| `/up` | 200 |
| `/` | 500 |
| `/resources` | 500 |
| `/learning` | 500 |
| `/tests` | 500 |
| `/sitemap.xml` | 500 |
| `/login` | 200 |
| `/password/forgot` | 200 |
| `/about` | 200 |
| `/admin` | 302 (authentication redirect) |

At the time of the 2026-11-19 inspection, the failing pages returned a database error because the `speaking_resources` table did not exist. The repository owner subsequently confirmed that the production migration has been applied. Re-run the affected public-route smoke checks after deployment and record the HTTP results; do not treat the historical 500 responses as the current state.

**Required recovery:**

The repository owner confirmed that a backup has been tested and the production migration has been applied. Remaining action: re-test the routes in the table above and inspect the migration result without exposing database credentials or learner data.

Do not run a production migration until the backup and restore prerequisite has been verified. No production migration was run during this inspection.

### 2.2 Exception details are exposed publicly

The homepage's HTTP 500 response contains a detailed SQL exception, database/table identifiers, SQL text and a stack trace. Treat production debug output as enabled or otherwise misconfigured until the runtime setting has been verified and the response no longer exposes exception details.

**Required recovery:**

1. Set `APP_DEBUG=false` in the private production environment.
2. Clear and rebuild Laravel's configuration cache using the approved production deployment procedure.
3. Request a controlled failing route and confirm the public response contains no exception message, SQL, file paths or stack trace.
4. Review access to existing logs and avoid copying raw production exception pages into tickets or chat.

PHP's Hostinger settings reported `display_errors=Off`, `log_errors=Off` and `expose_php=On`. These PHP settings do not make Laravel's detailed exception response safe. The application-level debug behaviour must be fixed independently.

## 3. Database backups and restore

**Status: Not verified. No restore test has been performed for this evidence snapshot.**

The connected Hostinger account interface exposed database inventory but did not expose shared/Cloud hosting backup points or backup retention through the available API. This does not establish that backups are absent.

The production owner must record the following from Hostinger hPanel or the applicable backup provider:

- Backup scope: database, website files, uploaded media and configuration.
- Backup frequency and the last successful backup timestamp.
- Retention period and number of recoverable restore points.
- Backup storage location, access controls and whether a copy is held independently of the hosting account.
- Who can initiate and approve a restore.
- The exact steps for restoring a database and files without overwriting production.

### Restore-test procedure

1. Select a recent backup and record its timestamp and source.
2. Restore it into a separate non-production database or isolated staging environment. Never overwrite the production database for a restore test.
3. Verify schema, row counts for representative tables, application connectivity and representative uploaded files.
4. Run the relevant Laravel migrations only in the isolated test environment if the restore is intended to validate a post-migration recovery scenario.
5. Record the restore start/end time, result, issues, and measured recovery time.
6. Delete or securely retain the test restore according to the approved data-handling policy.

Record the result here only after the test has actually succeeded:

| Field | Result |
|---|---|
| Backup provider and source | Not verified |
| Backup timestamp | Not verified |
| Restore-test date | Not performed |
| Restored target | Not performed |
| Result and evidence | Not performed |
| Recovery time | Not measured |
| Person performing/approving test | Not recorded |

## 4. File and media storage

The application configures Laravel's `public` disk as local storage under `storage/app/public`. The listening-resource model generates public URLs through that disk. The production directory was empty apart from its placeholder at inspection, and the hosting file listing did not show `public/storage`.

Before publishing or uploading media:

1. Confirm the intended media storage location and whether it is local or external.
2. Confirm the public storage link exists and points to the configured public disk. If local storage remains in use, create it through the approved deployment procedure with `php artisan storage:link`.
3. Upload a non-sensitive test asset and verify public retrieval, file validation, permissions and deletion behaviour.
4. Confirm uploaded files are included in backups and can be restored independently of the database.
5. Confirm private files cannot be fetched through public URLs.

No production storage link was created and no media was uploaded during this inspection.

## 5. Transactional email

The application uses Laravel's mail configuration. The local/test default remains the `log` mailer. Resend is the selected production transactional-email provider. The repository now includes the Symfony Resend transport, `MAIL_MAILER=resend` configuration guidance, the `RESEND_API_KEY` environment variable and a fail-closed readiness check for the Resend transport, key and sender. The production environment values are not readable through the available hosting interface.

The DNS zone currently contains:

- MX records pointing to Hostinger mail servers.
- SPF: `v=spf1 include:_spf.mail.hostinger.com ~all`.
- Three Hostinger DKIM CNAME records.
- DMARC: `v=DMARC1; p=none`.

Resend has been selected; existing Hostinger MX/SPF/DKIM records do not establish that the domain is authenticated with Resend. The Resend account, required DNS records, sender identity and current DMARC alignment remain unverified. The current DMARC policy is monitoring-only, not enforcement.

### Required mail verification

1. Create/confirm the Resend account and a production API key; store `RESEND_API_KEY` only in Hostinger environment secrets.
2. Authenticate `engleza.wiki` in Resend and publish the exact SPF/DKIM records Resend provides. Do not replace unrelated MX records without a separate mail-hosting decision.
3. Configure `MAIL_MAILER=resend`, `MAIL_FROM_ADDRESS` on the authenticated domain and `MAIL_FROM_NAME`.
4. Send a real account-verification email and password-reset email to controlled test mailboxes.
5. Verify successful delivery, message headers, spam placement and Resend delivery events.
6. Record the test date, sender domain and outcome. Do not record API keys, message contents or recipient personal data.

No real transactional email was sent during this inspection.

## 6. Logs, rotation and sensitive-data handling

Application logs are stored under `storage/logs`; the observed production file was `laravel.log` at approximately 4.66 MB. No dated Laravel log files were visible in the hosting file listing. The production environment's effective Laravel log channel and retention cannot be read through the available interface, so daily rotation must not be assumed.

The repository's intended production configuration is:

```dotenv
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14
```

The application includes a sensitive-data redaction processor and tests for redaction. This is a safety net, not permission to log credentials, tokens, session values or learner content.

Required verification:

- Confirm the effective Laravel log channel, level and retention in the private production environment.
- Confirm daily rotation and expiry by observing actual rotated files over time.
- Confirm who can read application and hosting logs.
- Confirm logs are included in the intended backup policy and do not contain secrets or unnecessary learner data.
- Review PHP and web-server error log destinations. Hostinger reported PHP `log_errors=Off`; confirm where PHP runtime errors are captured before relying on them for incident diagnosis.
- Keep detailed exceptions disabled in public responses.

## 7. Queue and scheduler

The inspected application has no classes implementing `ShouldQueue`, no job classes, no queue dispatch calls and no scheduled tasks. Laravel `schedule:list` reports no scheduled tasks. Hostinger reports no cron jobs for `engleza.wiki`.

Therefore, **the current application does not require a queue worker or Laravel scheduler**. Do not create a cron job or worker solely because the framework supports them. Reassess this when queued notifications, background jobs or scheduled commands are introduced.

The production environment's effective `QUEUE_CONNECTION` value was not independently verified. If the application begins dispatching database-backed jobs, configure and monitor a bounded worker process using the actual Hostinger-supported mechanism, and document retries, failed-job review and restart behaviour.

## 8. Monitoring and alerts

The live `/up` endpoint returned HTTP 200. No external uptime monitor, database health monitor or tested alert destination was evidenced by the repository or the available Hostinger account API. External monitoring may exist outside those sources; its status is **Not verified**.

At minimum, configure checks for:

- `https://engleza.wiki/up`.
- The homepage and representative public learning routes.
- Database connectivity and migration/schema health.
- Transactional email delivery.
- Backup completion and backup-storage capacity.

Route alerts to a monitored owner contact. Test the alert end-to-end, record who receives it and document the response expectation. A successful `/up` response alone does not prove that database-dependent learner journeys work.

## 9. Incident response and recovery

For a production outage or suspected data loss:

1. Record the time, affected routes/services and observed symptoms.
2. Preserve relevant logs and hosting evidence; do not publish raw logs or exception pages.
3. Identify whether the incident is code, migration/schema, database, storage, mail, DNS/CDN or hosting-related.
4. Confirm the latest usable backup before any destructive database or file operation.
5. Restore into isolation first when validating recovery or investigating corruption.
6. Use the approved deployment process for code rollback. Do not assume a code rollback is safe after a database migration.
7. Restore database or media only with explicit production-owner approval and a verified recovery point.
8. Re-run health checks and critical learner journeys; confirm mail and monitoring alerts.
9. Record the timeline, recovery point, data loss window, recovery time, root cause and follow-up actions.

### Escalation contacts

The production owner, backup approver, mail-provider contact and incident-alert recipient were not identified by the evidence collected for this runbook. Record named roles and the approved Hostinger/provider support route before launch. Do not invent contact details.

## 10. Outstanding acceptance evidence

The following items remain open until supported by live evidence:

- [x] Backup restore test completed (confirmed by repository owner).
- [ ] Verify public/private media storage and backup coverage.
- [ ] Configure Resend API key and verified sender in Hostinger; verify SPF/DKIM and test verification/password-reset delivery.
- [ ] Verify log access, rotation, retention and sensitive-data redaction in production.
- [ ] Configure and test uptime/database monitoring and alert routing.
- [x] Apply the `speaking_resources` production migration (confirmed by repository owner).
- [ ] Re-test the previously failing public routes after the migration.
- [ ] Review debug output before public launch. Debug output is intentionally enabled during active development and is not being treated as a current development blocker; do not expose detailed exception output on a public production deployment.
- [ ] Run and record a controlled production smoke test after remediation.
- [ ] Record production owner and escalation contacts.

**Do not declare production operations verified or close issue #24 until the outstanding evidence is collected and the required checks pass.**
