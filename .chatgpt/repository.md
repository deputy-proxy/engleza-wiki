# Repository-specific development configuration

Last verified: 2026-11-19. Re-check the live workflows and manifests before every implementation; this file is a snapshot, not a substitute for inspection.

## Runtime and framework
- Composer PHP requirement: `^8.3`; CI currently runs PHP 8.4.
- Laravel: 13.x.
- Filament: 5.x.
- Livewire: 4.x.
- Node.js: 22.
- Frontend package manager: Yarn Classic 1.22.22, using committed `yarn.lock`.
- CI database: MySQL 8.4.

## Current CI validation
Source: `.github/workflows/tests.yml`.

- PHP job: prepare `.env.testing`, install Composer dependencies, run MySQL migrations, then `composer ci:check`.
- `composer ci:check` runs Composer security audit, Pint formatting validation, PHPStan/Larastan, and Laravel tests.
- Frontend job: install PHP dependencies needed for Filament assets, enable Corepack and Yarn 1.22.22, run `yarn install --frozen-lockfile`, then `yarn build`.
- The current workflow does not define frontend interaction tests or an npm audit job.
- Assessment blueprint and learner-facing scope are documented in `docs/assessment/`; the current demo bank is not teacher-validated or piloted.
- CI caches Composer and Yarn dependencies and cancels obsolete runs.

## Local validation
- PHP: `composer ci:check`.
- Database: use MySQL 8.4 for workflow-equivalent migration and integration validation.
- Frontend: `corepack enable && corepack prepare yarn@1.22.22 --activate`, `yarn install --frozen-lockfile`, `yarn build`.
- Use focused Laravel tests while iterating, then run the complete workflow-equivalent validation before PR.
- Assessment bank checks: `php artisan assessment:bank-audit` audits prospective records and excludes grandfathered demo tests; `php artisan assessment:bank-audit --include-grandfathered` is diagnostic inventory only and does not approve or modify legacy content.
- Assessment bank regression tests: `php artisan test --compact tests/Feature/AssessmentBankAuditTest.php`.

## Repository-specific notes
- Do not replace Yarn with npm or regenerate the lockfile using another package manager without an explicit migration task.
- No frontend test script is currently defined in `package.json`; do not claim frontend tests run.
