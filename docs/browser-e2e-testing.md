# Browser end-to-end testing

The browser suite uses Playwright with Chromium to exercise the application through HTTP, including Laravel routing, sessions, CSRF protection, Blade and Alpine interactions.

## Local setup

Requirements: PHP extensions and dependencies from `composer.lock`, Node.js 22, Yarn Classic 1.22.22, and a MySQL 8.4 database matching `.env.testing`.

```sh
corepack enable
corepack prepare yarn@1.22.22 --activate
yarn install --frozen-lockfile
yarn playwright:install
cp .env.testing .env
APP_ENV=testing php artisan migrate:fresh --seed --force
yarn test:e2e
```

Playwright runs the browser fixture seeder automatically before each suite. It creates a deterministic writing class (join code `E2E26A`), assigns a published writing task, enrolls the seeded learner, creates a completed assessment attempt for result-page checks, and publishes a two-lesson learning path for progression checks. The fixture seeder refuses to run outside `APP_ENV=testing`.

The seeded development accounts are:
- Admin: `admin@engleza.wiki`
- Teacher: `teacher@engleza.wiki`
- Learner: `learner@engleza.wiki`

All use the development-only password `password`. Never use these accounts or fixtures in production.

## Environment

The application database is migrated and seeded with `APP_ENV=testing`. Playwright starts the HTTP server with `APP_ENV=local` so Laravel's normal CSRF middleware remains enabled. The E2E process uses `E2E_BASE_URL` (default `http://127.0.0.1:8000`), `APP_URL` and `ASSET_URL` set to that same origin, `CACHE_STORE=array`, `SESSION_DRIVER=database`, and `MAIL_MAILER=log`. Setting `ASSET_URL` ensures the browser loads the freshly built local Vite assets rather than an environment-specific host. Email verification messages are logged locally and no external mail provider is contacted.

## Coverage

The suite includes axe-core WCAG 2.2 A/AA scans of representative public and authenticated learner pages, including writing tasks, assessment results, dashboard, and learning paths, and asserts zero serious or critical axe violations, plus focused keyboard, mobile-navigation, form-label, validation, Livewire focus-retention, timer-warning, and reduced-motion accessibility regressions. It also covers public resource discovery, guest assessment attempt start and answer selection, registration and password confirmation, login and learner dashboard access, teacher class roster access, administrator panel authorization, learning-path enrolment and sequential lesson progression, listening and reading Livewire exercise interactions, learner writing submission with teacher feedback publication and learner visibility, CSRF rejection, and a mobile learner dashboard viewport. It intentionally tests cross-layer journeys rather than duplicating the focused PHPUnit and JavaScript unit tests.

## CI and diagnostics

GitHub Actions runs the browser suite against an isolated MySQL 8.4 service, builds frontend assets, installs Chromium, and uploads Playwright traces, screenshots, videos and HTML reports when available. Artifacts are retained for seven days.

Run `yarn test:e2e` locally. Use `yarn test:e2e:debug` for Playwright's inspector. For failures, inspect `playwright-report/` and `test-results/`; retry only after identifying whether the cause is an application defect, fixture issue, or transient browser infrastructure problem.
