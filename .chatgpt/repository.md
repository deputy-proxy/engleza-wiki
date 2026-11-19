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
- Frontend job: install PHP dependencies needed for Filament assets, enable Corepack and Yarn 1.22.22, run `yarn install --frozen-lockfile`, run `yarn test`, then `yarn build`.
- Frontend unit tests use Node.js built-in test runner (`node --test tests/js/*.test.js`); no third-party frontend test dependency is required. No npm audit job is defined.
- CI caches Composer and Yarn dependencies and cancels obsolete runs.

## Local validation
- PHP: `composer ci:check`.
- Database: use MySQL 8.4 for workflow-equivalent migration and integration validation.
- Frontend: `corepack enable && corepack prepare yarn@1.22.22 --activate`, `yarn install --frozen-lockfile`, `yarn test`, `yarn build`.
- Use focused Laravel tests while iterating, then run the complete workflow-equivalent validation before PR.

## Repository-specific notes
- Do not replace Yarn with npm or regenerate the lockfile using another package manager without an explicit migration task.
- Speaking practice uses guided prompts and self-reflection; it does not record audio or access the microphone. Timer logic is unit-tested, and a no-JavaScript fallback is rendered. Playwright browser verification passed in Chromium, Firefox and WebKit desktop engines, plus iPhone 13 emulation in Chromium/WebKit; physical iOS Safari and Android Chrome remain unverified.
