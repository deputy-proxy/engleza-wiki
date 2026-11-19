# Production operations runbook

**Status: operational controls are partially implemented. Resend is selected and implemented in the application; live mail delivery and external monitor/alert configuration remain outstanding.**
**Evidence snapshot:** 2026-11-20
**Environment:** `https://engleza.wiki`, hosted on Hostinger.

This document separates observations from assumptions. An item marked **Not verified** must not be represented as operationally ready. The initial live inspection found a production application error and a public exception page exposing database and stack-trace details. The repository owner subsequently confirmed the missing migration was applied and the post-migration smoke/recovery checks succeeded. Public production must still keep detailed exception output disabled.

## 1. Service inventory

| Area | Verified observation | Operational status |
|---|---|---|
| Hosting | Hostinger CloudLinux web hosting; Cloud Economy v3 order is active | Verified |
| PHP | PHP 8.3.36 | Verified |
| Database | A MySQL database is assigned to the domain; Hostinger reports approximately 7 MB used of a 6 GB quota | Connectivity and schema health not verified from the hosting API |
| TLS | Hostinger lifetime SSL certificate is active; HTTPS redirect is enabled | Verified |
| CDN | Hostinger CDN is enabled | Verified |
| Host cache | Server-side cache is disabled; cacheless/development mode is disabled | Verified |
| Public storage | Repository owner confirmed media storage and backup coverage are verified | Verified by owner confirmation; retain the operational evidence with the production records |
| Mail | Resend selected as the production transactional email provider; Laravel Resend transport and readiness checks are implemented in the repository. Existing DNS contains Hostinger MX/SPF/DKIM and DMARC `p=none` | Resend account, sender-domain authentication, API secret deployment and real delivery are not yet verified |
| Queue and scheduler | No application job classes, queue dispatches or scheduled tasks were found. Laravel `schedule:list` reports no scheduled tasks. No Hostinger cron job is configured for this website | No worker or scheduler is currently required by the inspected application code |
| Logs | Repository owner confirmed log access, rotation, retention and sensitive-data redaction are verified | Verified by owner confirmation; retain the operational evidence with the production records |
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

At the time of the 2026-11-19 inspection, the failing pages returned a database error because the `speaking_resources` table did not exist. The repository owner subsequently confirmed that the production migration was applied and the post-migration recovery/smoke-test work was verified. The historical 500 responses must not be treated as the current state. Retain the detailed route-by-route results in the restricted production operations record.

Do not run a production migration until the backup and restore prerequisite has been verified. No production migration was run during this inspection.

### 2.2 Exception details are exposed publicly

The homepage's historical HTTP 500 response contained a detailed SQL exception, database/table identifiers, SQL text and a stack trace. Debug output is intentionally enabled in the active development environment and is not a development blocker. The public production environment must independently use `APP_DEBUG=false`; do not expose detailed exception output on a public deployment.

**Required recovery:**

1. Set `APP_DEBUG=false` in the private production environment.
2. Clear and rebuild Laravel's configuration cache using the approved production deployment procedure.
3. Request a controlled failing route and confirm the public response contains no exception message, SQL, file paths or stack trace.
4. Review access to existing logs and avoid copying raw production exception pages into tickets or chat.

PHP's Hostinger settings reported `display_errors=Off`, `log_errors=Off` and `expose_php=On`. These PHP settings do not make Laravel's detailed exception response safe. The application-level debug behaviour must be fixed independently.

## 3. Database backups and restore

**Status: Verified by repository owner.** The owner confirmed that backup scheduling, retention, storage/access controls, restore testing, media storage and media backup coverage have been verified. The detailed provider, timestamps and test evidence should remain in the restricted production operations records.

The repository owner confirmed that the production backup schedule, retention, access controls, independent storage and restore coverage are verified. Detailed provider and backup-point evidence should remain in the restricted production operations record.

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

The repository owner confirmed the restore test succeeded. Detailed provider, backup timestamp, restore target, recovery time and named approver were not supplied in this repository discussion; retain those details in the restricted production operations record rather than inventing them here.

## 4. File and media storage

The repository owner confirmed that the production media storage setup, access behaviour and backup coverage have been verified. The initial inspection did not establish these facts; this section records the later owner confirmation. Keep the detailed test evidence with the restricted production operations records.

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

Application logs are stored under `storage/logs`. The repository owner confirmed that production log access, rotation, retention and sensitive-data redaction have been verified. Keep the detailed configuration and verification evidence with the restricted production operations records.

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

The application now exposes two deliberately separate public health checks:

- `GET https://engleza.wiki/up` is Laravel's liveness check. It confirms that the application process can serve HTTP requests.
- `GET https://engleza.wiki/health/ready` is the application readiness check. It verifies database connectivity and the presence of the core `users`, `migrations`, `resources` and `speaking_resources` tables. It returns only `{"status":"up"}` (HTTP 200) or `{"status":"down"}` (HTTP 503); database exception details are never returned to the caller.

The readiness endpoint runs outside the web/session middleware so it remains useful when the database-backed session store is unavailable. Automated feature tests cover a healthy database, a missing required application table and a simulated database connection failure. These endpoints are implemented in the repository; external uptime monitoring and alert delivery still require an account with a monitoring provider and a monitored recipient. Those provider-side actions cannot be completed from application code alone.

### External monitor setup

Configure an external HTTP monitoring provider (for example, UptimeRobot or Better Stack) with separate monitors for:

1. `https://engleza.wiki/up` (HTTP, expected 200).
2. `https://engleza.wiki/health/ready` (HTTP, expected 200; this is the database/schema readiness monitor).
3. `https://engleza.wiki/` and representative public learning routes such as `/resources` and `/learning` (expected 200; account for any intentional redirects).

Use a five-minute or shorter interval where supported, configure a failure threshold to reduce transient false alarms, and route alerts to a monitored owner address. Send a test alert and confirm receipt. Record the provider, monitor names, recipient role, test date and result in the restricted production operations record. Do not put provider tokens or private alert addresses in source control.

Database availability is checked by `/health/ready`; backup completion and capacity still need to be monitored through the backup provider or hosting account because the application has no backup scheduler or worker. Transactional mail delivery is verified separately through Resend delivery events and controlled test messages.

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

- [x] Backup schedule, retention, access controls, independent storage and restore test verified (confirmed by repository owner).
- [x] Verify public/private media storage and backup coverage (confirmed by repository owner).
- [ ] Configure Resend API key and verified sender in Hostinger; verify SPF/DKIM and test verification/password-reset delivery.
- [x] Verify log access, rotation, retention and sensitive-data redaction in production (confirmed by repository owner).
- [x] Implement application liveness and database/schema readiness endpoints with failure-safe responses and automated tests.
- [ ] Configure external uptime monitors and verify end-to-end alert delivery to the production owner.
- [x] Apply the `speaking_resources` production migration (confirmed by repository owner).
- [x] Post-migration recovery and public-route smoke test completed (confirmed by repository owner).
- [ ] Review debug output before public launch. Debug output is intentionally enabled during active development and is not being treated as a current development blocker; do not expose detailed exception output on a public production deployment.
- [x] Controlled production recovery and smoke-test procedures verified by repository owner; retain route-level results in production operations records.
- [ ] Record production owner and escalation contacts.

**The application-side monitoring implementation is complete. External monitor/alert setup and Resend live delivery are owner-operated follow-ups; do not represent them as completed until provider-side evidence exists.**
