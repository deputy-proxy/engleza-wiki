# Production deployment, migration verification and rollback

**Production:** `https://engleza.wiki`  
**Hosting:** Hostinger CloudLinux shared hosting  
**Deployment source:** GitHub repository `deputy-proxy/engleza-wiki`, branch `main`  
**Last configuration inspection:** 2026-11-20

This runbook documents the observed Hostinger configuration and the repository's current CI. It does not claim that a release is healthy merely because GitHub Actions passed.

Related operational references:

- [Production configuration](production-configuration.md)
- [Production operations runbook](operations-runbook.md)

## 1. Verified deployment topology

The Hostinger account currently reports:

| Setting | Observed value |
|---|---|
| Website | `engleza.wiki` |
| Hosting | CloudLinux shared hosting, Cloud Economy v3 |
| Website document root | `/home/u583712040/domains/engleza.wiki/public_html` |
| Git auto-deployment | Enabled |
| Git provider/repository | GitHub, `deputy-proxy/engleza-wiki` |
| Deployment branch | `main` |
| Deployment directory | Website document root (empty relative directory) |
| PHP web runtime | PHP 8.3.36 |
| CI PHP runtime | PHP 8.4 |
| CI Node runtime | Node.js 22 |
| CI package manager | Yarn Classic 1.22.22 |
| Hostinger Node build | None reported; this is a PHP website, not a Hostinger Node.js app |

The website document root is the Laravel project root, not Laravel's `public/` directory. The tracked root `.htaccess` rewrites requests into `public/`; `public/.htaccess` disables directory indexes and routes application requests through `public/index.php`. Preserve this arrangement unless the hosting document root and rewrite rules are deliberately changed together.

The live document-root inventory contains the Laravel project, `vendor/`, `node_modules/`, `public/build/`, and a hosting-side `package-lock.json`. The repository uses `yarn.lock` and ignores generated `vendor/`, `node_modules/`, and `public/build/`. The presence of these generated directories on the server does not prove that Hostinger refreshes them on every Git deployment.

### What automatic deployment does and does not establish

- Hostinger's GitHub auto-deployment setting is enabled for pushes/updates to `main`, targeting the website root.
- There is no deployment workflow in `.github/workflows/`. The only workflow is `tests.yml`, which validates PHP and frontend code; it does not upload artifacts or deploy the site.
- Hostinger reports no Node.js build for this PHP website. Do not assume it runs `yarn build`.
- The available Hostinger configuration confirms the repository, branch and destination, but does not expose a reliable record of Composer hooks, frontend build hooks, or the last deployed commit.
- Consequently, a successful Git deployment must not be assumed to update Composer dependencies or Vite assets. Verify and refresh those explicitly using the post-deployment procedure below.
- Do not configure a second deployment mechanism, add a deployment script, or run a manual Hostinger Git deployment alongside the existing auto-deployment without an explicit infrastructure change.

Hostinger auto-deployment is triggered by changes to `main`. The pull request must therefore pass its required checks before merge. The push-to-`main` workflow is a subsequent verification, not a substitute for requiring PR checks before merging.

## 2. Runtime compatibility

The production web runtime is PHP 8.3.36. The repository requires PHP `^8.3`, so this satisfies the declared PHP constraint. CI currently runs PHP 8.4; keep production at a supported PHP version and test compatibility before changing the hosting runtime.

The Hostinger API inspection did not expose the production CLI Composer, Node.js or Yarn versions, nor confirm SSH/terminal availability. Do not invent those values. Before the first release using this procedure, connect through the hosting account's approved terminal/SSH facility and record:

```bash
php -v
composer --version
node --version
yarn --version
```

The CI reference versions are PHP 8.4, Composer 2, Node.js 22 and Yarn 1.22.22. Use the same major/minor toolchain where Hostinger supports it. The application uses Yarn Classic and the committed `yarn.lock`; do not use npm or the server's untracked `package-lock.json` to install frontend dependencies.

If an approved terminal/SSH facility is not available, resolve that access prerequisite with the hosting owner before relying on manual dependency and migration commands. Do not improvise a web-accessible deployment endpoint.

## 3. Release preflight

Do not merge a production release until all applicable checks below are complete:

- [ ] The change is reviewed in a pull request targeting `main`.
- [ ] All required pull-request GitHub Actions jobs are green: PHP security audit, Pint, Larastan/PHPStan, Laravel tests, frontend unit tests and Vite production build.
- [ ] The PR includes the correct `composer.lock` and `yarn.lock` when dependencies change. No package manager has silently regenerated or replaced a lockfile.
- [ ] The deployment diff has been reviewed for secrets, local files, generated artifacts, database dumps, and accidental changes to `.env` or hosting-only files.
- [ ] Migration files have been reviewed for table locks, data rewrites, destructive operations, runtime and storage impact, and compatibility with the currently deployed code.
- [ ] The current production backup is recent and usable. Confirm database, website files and uploaded media are covered; record the backup point and approval in the restricted production operations record.
- [ ] A restore path has been confirmed before any destructive or hard-to-reverse migration.
- [ ] The production PHP runtime (8.3.36 at the last inspection) satisfies the application's Composer platform requirements.
- [ ] Any required manual Hostinger CLI commands can be run by an authorized operator.
- [ ] A rollback or forward-fix decision has been made for schema changes.

Do not run `migrate:fresh`, `db:wipe`, destructive seeders, or an unreviewed `migrate:rollback` against production.

## 4. Deployment sequence

1. Merge the reviewed PR only after required PR checks pass. The merge updates `main` and triggers the existing Hostinger GitHub auto-deployment.
2. Wait for Hostinger's deployment operation to finish. The current integration does not provide a dependable deployed-commit identifier in the inspected settings, so record the merge commit and deployment time in the release record.
3. Connect to the Hostinger website using the approved terminal/SSH facility and enter the verified Laravel project root:

   ```bash
   cd ~/domains/engleza.wiki/public_html
   pwd
   php -v
   composer --version
   node --version
   yarn --version
   ```

   Confirm the runtime versions against the release preflight. Use `git rev-parse HEAD` only if the Hostinger-managed checkout actually contains Git metadata; the Hostinger file inventory does not establish that `.git/` is present. Otherwise, confirm the completed deployment and deployed revision through Hostinger's deployment history/logs. If neither method can establish that the intended `main` revision was deployed, stop and reconcile the deployment before running application commands.

4. Refresh PHP dependencies when `composer.json` or `composer.lock` changed, or when the deployed `vendor/` state is uncertain:

   ```bash
   composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader
   ```

5. Refresh frontend dependencies and production assets when frontend manifests, lockfiles, or source assets changed, or when the generated build is uncertain:

   ```bash
   corepack enable
   corepack prepare yarn@1.22.22 --activate
   yarn install --frozen-lockfile
   yarn build
   ```

   Confirm that `public/build/manifest.json` exists and references the generated assets. Never delete the previous build before a successful replacement is ready. The frontend CI build does not publish these files to Hostinger.

6. Confirm the production `.env` remains in place and contains the approved production configuration. Never copy `.env.example` or `.env.testing` over it. Preserve `APP_KEY`, database credentials, mail credentials and all other secrets.
7. Confirm the backup prerequisite again immediately before migrations. Run migrations only when the release contains reviewed migrations:

   ```bash
   php artisan migrate --force
   php artisan migrate:status
   ```

   Confirm every expected migration is marked as run. Investigate any pending or failed migration before continuing.

8. Rebuild Laravel's optimized caches after dependencies, assets and migrations are in place:

   ```bash
   php artisan optimize
   ```

9. Run the fail-closed production configuration check:

   ```bash
   php artisan app:production-readiness
   ```

   Resolve failures before declaring the release healthy. This command checks configuration and core service readiness; it does not replace HTTP smoke tests, restore testing or real mail delivery.

Do not run a generic deployment script: none is part of this repository's verified deployment setup. Do not run Composer or Yarn on every release if their manifests/lockfiles and generated outputs are unchanged and their deployed state has been verified.

## 5. Post-deployment verification

Run the checks from an independent client after Hostinger deployment and the manual post-deployment steps:

| Check | Expected result |
|---|---|
| `GET https://engleza.wiki/up` | HTTP 200 |
| `GET https://engleza.wiki/health/ready` | HTTP 200 with `{"status":"up"}` |
| `GET https://engleza.wiki/` | HTTP 200 |
| `GET https://engleza.wiki/resources` | HTTP 200 |
| `GET https://engleza.wiki/learning` | HTTP 200 |
| `GET https://engleza.wiki/login` | HTTP 200 |
| `GET https://engleza.wiki/password/forgot` | HTTP 200 |
| `GET https://engleza.wiki/admin` | Authentication redirect for a signed-out visitor |
| `GET https://engleza.wiki/sitemap.xml` | HTTP 200 with valid XML |

Example status-only smoke check (does not print response bodies):

```bash
for path in /up /health/ready / /resources /learning /login /password/forgot /admin /sitemap.xml; do
    printf '%-24s ' "$path"
    curl --silent --show-error --output /dev/null \
        --write-out '%{http_code}\n' "https://engleza.wiki$path"
done
```

Also verify:

- [ ] `php artisan app:production-readiness` exits successfully.
- [ ] The Vite manifest and every referenced asset return successfully; no stale asset references or browser-console errors.
- [ ] Login, email verification and password recovery work with controlled test accounts.
- [ ] A representative learner journey works, including a published resource and an exercise submission.
- [ ] The admin authentication boundary behaves as expected.
- [ ] HTTPS redirects, Hostinger CDN response header `x-hcdn-request-id`, and secure session-cookie attributes remain correct.
- [ ] No public response contains exception messages, SQL, file paths, environment values or stack traces.
- [ ] Application logs show no new deployment-related exceptions or repeated failed migrations.
- [ ] Any real transactional-email check is performed with controlled mailboxes and verified through Resend delivery events; do not treat the readiness command as proof of delivery.

A failed readiness endpoint or public route is a failed release, even if `/up` returns 200. Capture status codes, timestamps and redacted diagnostics in the restricted operations record. Never paste raw production exception pages, logs, credentials or learner data into a pull request.

## 6. Migration safety

### Prefer additive, backward-compatible migrations

- Add nullable columns, new tables, indexes and compatible constraints before changing application code to depend on them.
- Deploy code that can tolerate both the old and new schema during a transition.
- Backfill large datasets in bounded, resumable batches using an explicit operational procedure. Do not put an unbounded table rewrite into a normal web request or assume a migration will finish within Hostinger's request timeout.
- Add new writes/reads only after the expanded schema is available.
- Remove old columns, tables or constraints only in a later release after all deployed code no longer uses them and the retention/legal implications have been reviewed.
- For renames or type changes, use expand/contract: add the replacement, dual-read/write or backfill as appropriate, verify, switch consumers, and remove the old structure in a later release.
- Add indexes with awareness of production table size, lock behaviour and Hostinger's database capabilities. Test the migration against a representative MySQL version and data volume.
- Keep migrations deterministic and safe to run once. Do not edit an already-applied migration to change production history.

### Destructive or long-running changes

Before a destructive change:

1. Take and verify a restorable database and file/media backup.
2. Confirm the recovery point, owner approval and maintenance window.
3. Estimate table size, lock duration, available storage and migration runtime using a representative environment.
4. Prefer a staged expand/contract migration over a destructive one-step change.
5. If a maintenance window is unavoidable, announce it, enable maintenance mode only for the planned operation, and confirm the operator can restore service even if the command fails.
6. Run the migration and verify schema, representative records, readiness and critical journeys before ending the maintenance window.

Do not assume that a database restore is harmless: restoring an older snapshot can discard legitimate learner registrations, assessments, writing submissions, feedback and progress created after that snapshot.

## 7. Rollback and recovery decision tree

### A. Code is faulty; the database remains compatible

1. Stop further releases and preserve deployment logs.
2. Confirm the previous application revision is compatible with the current schema and data.
3. Revert the faulty change through a reviewed PR to `main`; Hostinger auto-deployment will deploy the resulting commit.
4. Refresh dependencies/assets if the revert changed their manifests or source.
5. Re-run the migration/readiness and HTTP smoke checks.
6. Record the incident, reverted commit and verification results.

Do not force-push or manually rewrite the production checkout as the normal rollback method.

### B. Migration failed before completion

1. Do not blindly rerun or roll back the migration.
2. Inspect the migration status and the first meaningful database error using authorized access.
3. Determine whether the migration ran inside a transaction and whether the database engine rolled it back.
4. Check actual schema/data state against the migration's intended changes.
5. If safe, correct the cause and deploy a forward migration. Otherwise restore only with explicit production-owner approval and a verified recovery plan.

### C. New code requires schema changes that cannot be reversed safely

Do not roll back code to a revision that expects the old schema. Deploy a compatible forward fix or restore code and data together under an approved recovery plan.

### D. Data corruption or loss

1. Declare an incident and stop destructive writes where practical.
2. Preserve evidence and identify the last known-good recovery point.
3. Restore database/files/media into an isolated target first.
4. Verify schema, representative records, uploaded media and application compatibility.
5. Obtain explicit production-owner approval before replacing production data.
6. Record the recovery point, estimated data-loss window, recovery time and post-restore checks.

A code rollback and a database restore are different operations. Never restore a database solely because a code deployment failed.

## 8. Release record

For each release, retain the following in the restricted production operations record:

- PR URL, merge commit SHA and deployment timestamp.
- Hostinger deployment result and target branch.
- Whether Composer dependencies or Vite assets were refreshed.
- Backup reference and approval before migrations.
- Migration names and result.
- Production readiness and HTTP smoke-test results.
- Controlled authentication/learner/admin checks.
- Any incident, rollback, forward fix or outstanding limitation.

Do not store credentials, raw environment files, personal data or unredacted production logs in the release record.

## 9. Current verification snapshot (2026-11-20)

The Hostinger account confirms GitHub auto-deployment is enabled for `main` and the website root. The current repository has only a CI workflow; it has no GitHub deployment job. Hostinger reports no Node.js build for this PHP website. Production PHP is 8.3.36. The production CLI Composer, Node.js and Yarn versions and the automatic dependency/build hooks were not exposed by the available hosting configuration.

A live HTTP check during this runbook update returned:

| Endpoint | Observed result |
|---|---:|
| `/up` | HTTP 200 |
| `/health/ready` | HTTP 503 |
| `/` | HTTP 500 |

This means the application process is reachable but the database/schema readiness check and homepage are failing at the time of this snapshot. The public error response was not inspected for sensitive details. Treat production as unhealthy until the cause is diagnosed and the readiness and public-route checks pass. Do not run migrations or restore production data merely to make this runbook's checklist green.

A controlled release/deployment rehearsal was not performed as part of this documentation change. The available production environment was already failing its readiness and homepage checks, and no separate staging target was verified. Complete a controlled deployment rehearsal against a healthy, isolated staging target before marking this runbook's deployment-validation acceptance criterion complete.
