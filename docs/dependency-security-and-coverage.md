# Dependency security and PHP test coverage

## Dependency monitoring

Dependabot checks the Composer and Yarn dependency graphs weekly and opens update pull requests against `development`. Minor and patch updates are grouped; major updates remain separate so maintainers can review compatibility and migration risk. There is no automatic merge configuration.

CI runs both dependency audits:

- `composer audit --no-interaction` through `composer ci:check`.
- `yarn audit --level moderate` across production and development dependencies.

The frontend audit originally identified advisories in Axios' `follow-redirects` dependency, `concurrently`'s `shell-quote` dependency, and Vite's `source-map-js` / `launch-editor` dependency chain. The lockfile was updated within the existing manifest ranges. The current audited lockfile reports zero Yarn advisories; Composer reports no security advisories.

### Advisory triage and ownership

Repository maintainers own dependency advisory triage and update pull requests. For each advisory, record:

1. Severity and whether the affected package is installed in production, used only during builds/tests, or is not reachable in the deployed application.
2. The vulnerable dependency path and the feature or build process that can reach it.
3. Whether the vulnerable code path is exposed to untrusted input in this application.
4. The patched version, available mitigations, and compatibility/migration impact.
5. The responsible maintainer and the next review date if an immediate patch is not available.

Critical and high severity advisories receive priority review. Moderate and low severity findings are still reviewed, with urgency based on exposure and exploitability rather than severity labels alone. Security fixes must pass the normal CI checks. Major version updates require explicit maintainer review; Dependabot is not configured to auto-merge.

The repository integration available during this change exposes GitHub repository reads but not the mutation endpoint for enabling repository-level Dependabot alerts/security updates. The Dependabot update schedule is committed here; a repository administrator must also confirm **Settings → Code security and analysis → Dependabot alerts** and **Dependabot security updates** are enabled. This setting is separate from the version-update schedule in `.github/dependabot.yml`.

## PHP coverage reporting

PHPUnit measures the application source under `app/` using PCOV. There are no source exclusions. The first measured baseline, before the additional curriculum regression tests in this issue, was:

- Statements: 4,073 / 5,120 (**79.55%**).
- Methods: 429 / 660 (**65.00%**).
- Test cases in that baseline run: 206.

The CI floors are deliberately below the measured baseline:

- **75% statement coverage**.
- **60% method coverage**.

These are guardrails against substantial regressions, not a target for adding low-value tests. Raise them only after a later measured baseline demonstrates sustained headroom. Do not lower them simply to silence a failure. Add tests for uncovered decisions and important failure paths; do not exclude application files to improve the percentage.

The report is generated at `build/coverage/clover.xml`, checked by `scripts/check-coverage.php`, and uploaded as the `php-coverage-report` CI artifact for 14 days. The step summary contains only aggregate counts and percentages. The coverage report contains source paths and hit counts, not learner records or application secrets.

### Current high-risk test areas

The suite has dedicated feature coverage for user authorization, assessment attempt lifecycle/scoring, learner progress, curriculum access/completion, writing submissions, and privacy/data-rights workflows. To avoid implying that one percentage describes an entire domain, the following baseline is a curated slice of high-risk application files (statement coverage only, before the new curriculum tests):

| Area | Covered statements | Coverage |
| --- | ---: | ---: |
| Authorization policy and user-management resource | 19 / 19 | 100.0% |
| Assessment attempt lifecycle and scoring | 425 / 462 | 92.0% |
| Learner progress | 464 / 515 | 90.1% |
| Curriculum and learning-path lifecycle | 187 / 271 | 69.0% |
| Writing submissions | 82 / 94 | 87.2% |
| Privacy and data rights | 182 / 188 | 96.8% |

These slices are deliberately explicit file selections, not claims about every controller, model, or UI path in each domain. The measured review identified learning-path activity validation and final path-enrollment completion as the clearest gap; focused regression tests now cover assessment-attempt ownership and completion of the final available lesson. The curriculum slice increased from 187 / 271 (69.0%) to 216 / 271 (79.7%). The existing feature suites continue to exercise the other high-risk areas, so this change does not add duplicate tests solely to move their percentages.

After the changes, the full suite passed **208 tests and 1,891 assertions**, with **80.12% statement coverage** (4,102 / 5,120) and **65.15% method coverage** (430 / 660). Both documented floors remain satisfied.

## Local commands

Install the project dependencies using the committed lockfiles, then enable PCOV for the active PHP CLI:

- Ubuntu/Debian: install the matching `php-pcov` package for the installed PHP version.
- Verify the extension with `php -m | grep -i pcov`.

Run the checks:

```sh
composer audit --no-interaction
composer test:coverage
composer ci:check
yarn audit --level moderate
yarn test
yarn build
```

The full CI PHP job runs on PHP 8.4 with PCOV and MySQL 8.4. The local sandbox used for this baseline had PHP 8.5 and MySQL; CI remains the independent validation gate.
