# Repository-specific development configuration

Last verified: 2026-11-20. Exact resolved versions are recorded in the cross-repository matrix at [Cr8or](https://github.com/deputy-proxy/cr8or/blob/main/.chatgpt/repository-versions.md). Re-check manifests, lockfiles, and workflows before each implementation.

## Runtime and resolved framework versions
- PHP constraint: `^8.3`; CI uses PHP 8.4.
- Laravel 13.42.0; Filament 5.11.4; Livewire 4.4.8.
- PHPUnit 12.5.44; Larastan 3.15.0; Pint 1.32.3.
- Node.js 22; Yarn Classic 1.22.22.
- Yarn lockfile resolves Tailwind CSS 4.3.3 and Laravel Vite plugin 3.2.0. The exact Vite package version was not found in the retrieved lockfile; manifest declares `^8.0.0`.
- Pest is not currently installed.

## Current CI validation
Source: `.github/workflows/tests.yml`.
- PHP job: prepare `.env.testing`, install Composer dependencies, run MySQL 8.4 migrations, then `composer ci:check` (Composer security audit, Pint, PHPStan/Larastan, Laravel tests).
- Frontend job: install PHP dependencies for Filament assets, set up Node.js 22, enable Corepack/Yarn 1.22.22, run `yarn install --frozen-lockfile`, `yarn test` (Node.js unit tests), then `yarn build`.
- No browser end-to-end test job or npm audit job is defined.

## Local validation
- PHP: `composer ci:check`.
- Database: MySQL 8.4 for workflow-equivalent migration/integration checks.
- Frontend: `corepack enable && corepack prepare yarn@1.22.22 --activate`, `yarn install --frozen-lockfile`, `yarn test`, `yarn build`.
- Production deployment: Hostinger GitHub auto-deployment is enabled for `main` into the website document root. Production PHP was verified as 8.3.36; CI uses PHP 8.4 and Node.js 22. Hostinger CLI Composer/Node/Yarn versions and automatic build hooks were not exposed. Follow `docs/deployment-runbook.md`; do not assume Hostinger refreshes `vendor/` or `public/build/` on Git deploy.
- Do not replace Yarn with npm or regenerate its lockfile using another package manager without an explicit migration task.


## Browser end-to-end tests
- Playwright + Chromium; config: `playwright.config.js`; tests: `tests/e2e/`.
- Commands: `yarn test:e2e`, `yarn test:e2e:debug`, `yarn playwright:install` (`yarn playwright:install:ci` in Linux CI).
- CI uses a separate MySQL 8.4 service, runs `migrate:fresh --seed`, then `BrowserTestSeeder`, builds assets, installs Chromium, and runs the browser suite.
- `BrowserTestSeeder` is testing-environment-only. It approves seeded demo content for deterministic browsing and creates the E2E writing-class and two-lesson curriculum fixtures.
- Details and covered journeys: `docs/browser-e2e-testing.md`.
