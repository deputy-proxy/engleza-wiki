# engleza.wiki

> Free knowledge. Better English.

**engleza.wiki** is a free English-learning knowledge and assessment platform for learners, teachers, and parents. It combines a public educational resource website, a free CEFR-aligned English testing platform, and a comprehensive Filament admin panel.

## Stack

- **PHP 8.3+** + **Laravel 13.42** (framework)
- **Filament 5.11** (admin panel)
- **Blade** for public views
- **Livewire 4.4** for interactive components and the test engine
- **Alpine.js** for small client interactions (timer, transitions)
- **Tailwind CSS 4** for styling (via Vite)
- **MySQL / MariaDB** for persistence

No React, Vue, Inertia, Bootstrap, jQuery, DaisyUI or other component libraries are used.

## Local development

The committed Composer lockfile is resolved against PHP 8.3 (`config.platform.php`) so that the full dependency tree remains installable on the declared minimum runtime. CI currently validates on PHP 8.4. The frontend build uses Vite 8, which requires Node 22.12+ or Node 20.19+.

Prerequisites: PHP 8.3+, Composer 2, Node 22.12+ (or Node 20.19+), Yarn 1.22.22, MySQL/MariaDB.

```bash
# 1. Install dependencies
composer install
yarn install --frozen-lockfile

# 2. Environment
cp .env.example .env   # (or edit /app/.env directly)
php artisan key:generate

# 3. Database — create db + user
mysql -e "CREATE DATABASE engleza_wiki; CREATE USER 'engleza'@'localhost' IDENTIFIED BY 'engleza_pass'; GRANT ALL ON engleza_wiki.* TO 'engleza'@'localhost'; FLUSH PRIVILEGES;"

# 4. Run migrations + seed the demo content
php artisan migrate:fresh --seed

# 5. Build frontend assets
yarn build         # (or yarn dev for Vite HMR)

# 6. Serve
php -S 0.0.0.0:3000 -t public public/server.php
```

Open <http://localhost:3000>.

## Admin panel

- URL: **/admin**
- Local development/test credentials only (all with password `password`; never use these in production):

| Role     | Email                       | Password  |
|----------|-----------------------------|-----------|
| Admin    | admin@engleza.wiki          | password  |
| Editor   | editor@engleza.wiki         | password  |
| Teacher  | teacher@engleza.wiki        | password  |
| Learner  | learner@engleza.wiki        | password  |

Only `admin` and `editor` roles can access `/admin`. User management is admin-only; account deletion is blocked when it would remove the last administrator or cascade-delete learner/class history. See [Admin panel security](docs/admin-security.md) for the role matrix and recovery procedure. See [Account lifecycle](docs/account-lifecycle.md) for registration, verification, password recovery and session security.

## Editorial quality and publication review

New resources, tests, lessons and translations require explicit editorial approval before they are served publicly. New content starts as `pending`; edits to source content, answer keys, interactive child records or translations invalidate the affected review. Content that existed when the workflow was deployed is marked `grandfathered`: it remains available as demo content, is not represented as reviewed, and is excluded from editorial and translation audits. Pending/revision content is excluded from public listings, direct routes, adaptive practice, search and the sitemap. `noindex` content may remain accessible but is excluded from the sitemap and receives a noindex directive.

Editors manage decisions in the existing Filament Content area. The Translations resource supports Romanian translation editing and approval. A new translation is displayed only when its translation status is `reviewed`, its editorial status is `approved`, and its source hash is current. Grandfathered demo translations retain their previous behavior. Otherwise the original English source is used as the fallback.

The rubric, CEFR content standards, publication workflow and reviewer responsibilities are documented in `docs/editorial/`. Editorial audits are prospective: they only inspect records created after the workflow rollout. Existing demo content is grandfathered and is not retroactively audited or blocked. New records remain unpublished until editorial approval. Translation checks follow the same prospective boundary.
## Assessment and learning attempts

Anonymous visitors can take free tests without registering. Attempts are owned by the authenticated learner or the originating anonymous session. Test and learning attempts persist their assigned question IDs at start, so questions added later cannot silently join an in-progress attempt and shuffled navigation remains stable. An interrupted started attempt can be reopened through its existing attempt URL; its saved answers and question order are restored.

Scoring is server-authoritative and shared through `App\Services\Assessment\QuestionScoringService`:

- Every assigned question contributes its configured points to the denominator.
- Unanswered questions earn zero points.
- Percentages are rounded to one decimal place before the whole-number passing threshold is evaluated.
- Empty assessments return 0% and cannot pass.
- Section and topic breakdowns use the same complete question set and are persisted with the result.
- Test results include a CEFR estimate and recommendations based on weak topics.

Test access rules: `free` allows guest and registered learners; `registered` requires authentication; `premium` is currently unavailable because the repository has no subscription/entitlement model, so it is deliberately denied rather than treated as free. Expired attempts retain and score answers saved before the deadline, remain marked `expired`, and do not receive completion certificates. Repeated submissions return the existing result. Correct answers and explanations are withheld until a completed attempt and then follow `show_correct_answers`; listening and reading resources have the same configurable disclosure toggle, defaulting to enabled to preserve existing behaviour.

Historical attempts are not recalculated against edited content. If their original question set was never recorded, their existing score/history is preserved and no potentially misleading question review is generated.

## Learning paths and reusable exercises

Learning paths are published sequences of ordered units, lessons and activities. A lesson activity references an existing resource or test, so content remains canonical and reusable instead of being copied into a course. Lessons and paths carry CEFR level, skills, topics, objectives, publication dates and ordering metadata. Draft and archived content is not learner-visible.

Create and publish content in `/admin` using **Learning paths**, **Units**, **Lessons** and **Activities**. Edit pages provide nested managers: manage units from a path, lessons from a unit and ordered activities from a lesson. Recommended workflow: create a draft path; add and publish units; create draft lessons and link existing resources/tests; publish each lesson after its unit and referenced content are ready; then publish the path. Model validation requires a publication date, a published unit, at least one valid activity and supported question definitions for linked interactive resources/tests. Published lesson activities cannot be edited or removed in place: unpublish the lesson, make changes, then republish it to re-run readiness checks.

Learners browse `/learning`, enrol in a path, and continue from `/my-learning`. Lessons unlock in server-enforced order. Required activities must be completed before a lesson can be completed. Listening/reading activities require a completed owned learning attempt; writing activities require a submitted writing response; test activities require a completed owned test attempt. Other resource activities can be manually marked complete from the lesson page. Progress is stored per learner, lesson and activity. In-progress attempts and lessons can be resumed. Completed lessons remain complete and reviewable; learners may revisit resources and create new assessment attempts without overwriting historical attempts or resetting lesson completion. A resource page linked from a lesson shows its lesson context.

The shared question-response engine supports `single_choice`, `multiple_choice`, `true_false`, `text`, `short_text`, `matching`, `ordering` and `gap_fill` in both formal tests and listening/reading exercises. Matching, ordering and gap-fill definitions are authored in the Question form's **Exercise configuration (JSON)** field. Structured definitions are validated before saving, and lesson publication checks every linked interactive question. Learner payloads omit answer keys and correctness flags until a completed attempt and only disclose them when the resource/test setting permits it.

Scoring rules: single-choice, true/false and text answers are all-or-nothing; multiple choice uses positive partial credit based on correct selections minus incorrect selections, divided by the number of correct options; matching awards credit per correct pair; ordering awards credit per item in its correct position; gap-fill awards credit per correctly completed gap. Partial-credit scores are clamped between zero and the question's maximum points.

## Learner progress, spaced repetition and adaptive practice

Learner progress is derived from completed, scored formal test attempts, interactive learning attempts and adaptive practice sessions. In-progress, abandoned and expired attempts are excluded. Lesson completion is tracked separately and is never treated as evidence of mastery. Raw attempts and answers remain immutable; the progress profile is recalculated from source evidence when requested.

### Mastery model

- Each answered question is normalised to a 0–1 result using `points_awarded / question.points`. Partial-credit exercise types therefore contribute proportionally without mixing incompatible raw point scales.
- Evidence weight is the product of a difficulty factor (`easy` 0.85, `medium` 1.0, `hard` 1.15), a 60-day recency half-life, and a repeat discount of `1 / sqrt(exposures to the same question)` within the dimension.
- A topic or skill receives a mastery percentage only after at least **five distinct questions across two completed attempts**. Before that, the observed percentage is available for diagnostics, but the state is `insufficient_evidence`, not low mastery.
- With sufficient evidence, mastery below 60% is `needs_practice`, 60–79.9% is `developing`, and 80% or above is `strong`. Confidence is `moderate` at the minimum evidence threshold and `high` at ten distinct questions across three attempts.
- Skills are aggregated only from evidence explicitly linked to that skill (the test's skill or a resource's skills). Topics use question-topic links, falling back to resource topics for interactive learning attempts. Different skills/topics are never averaged into one proficiency score.
- Adaptive practice contributes at 0.75 evidence weight so guided practice informs mastery without outweighing formal assessment. The displayed CEFR estimate comes from the latest completed formal test result, falling back to that test's assigned CEFR level. Lesson level and completion are not silently converted into an assessment result.

### Vocabulary review policy

A learner encounters review items when opening a published flashcard deck, a vocabulary-tagged listening/reading resource, or a vocabulary-tagged lesson/test activity. Supported question sources include accepted text/short-text answers, single-choice/true-false answers, matching pairs and gap-fill accepted answers. Items are deduplicated per learner using a SHA-256 identity of the normalised prompt and answer, so repeated appearances in multiple decks do not create duplicate queue entries. Private learner review history is stored separately from public source content.

The deterministic scheduler uses four grades:

- **Again:** retry in 10 minutes, reset successful repetitions, increment lapses, reduce ease by 0.20.
- **Hard:** schedule at 1.2 × the previous interval (minimum one day), reduce ease by 0.15.
- **Good:** first intervals are one day and three days; later intervals multiply the previous interval by the ease factor.
- **Easy:** first intervals are four days and seven days; later intervals multiply the previous interval by 1.3 × the ease factor.

Ease starts at 2.50 and is bounded between 1.30 and 3.00. Every review creates an auditable event with the grade, previous/new interval and review time. Learners can review due items at `/reviews` and see items scheduled in the next seven days.

### Adaptive practice

`/practice/start` creates a separate adaptive practice session from currently published, valid questions in public tests and listening/reading resources. The selector prioritises topics with demonstrated low mastery, applies explicit difficulty bounds (no CEFR evidence or A1/A2: easy–medium; B1/B2: easy–hard, rising to medium–hard with strong mastery; C1/C2: medium–hard; demonstrated mastery below 55%: easy–medium), selects up to ten questions, and excludes questions answered in the previous 14 days when alternatives exist. If every eligible question has been used recently, a deliberate revisit is allowed rather than returning an empty session. Responses, scores and answer disclosure belong to the adaptive session; formal test attempts, assigned question snapshots and scoring are unchanged.

Teachers use the authenticated `/teaching` workspace to create/manage their own classes, share invite links, add/remove learners, assign explicitly approved published tests and writing tasks, transfer class ownership and archive/reopen classes. Learners explicitly join through the class invite link. Teacher rosters and reports include active members only; removed learners disappear from current reports while historical records are retained. Progress summaries use completed attempts attached to that exact class group and exclude unrelated private activity or progress from other classes. See [teacher and class workflows](docs/teacher/class-workflows.md).

### Deployment note

Run `php artisan migrate --force` during deployment. The assessment-integrity migrations add nullable attempt metadata and score-breakdown fields without rewriting historical scores. The curriculum migrations create new path, unit, lesson, activity and progress tables. The exercise-engine migration adds nullable JSON response payloads to test and learning answers. The learner-progress migration creates learner-specific vocabulary items and review events, isolated adaptive-practice sessions/answers, and indexes for completed learner attempts. The teacher-class workflow migration adds auditable memberships and ownership transfers, backfills existing class-linked attempts/submissions as historical memberships, and preserves class history with restrictive foreign keys. Existing option/text columns remain intact and historical answers are not backfilled. Historical attempts without a saved question set retain their existing score but do not receive a reconstructed question review. Existing listening/reading resources default to showing answer explanations after completion, preserving the previous behaviour; the new toggle can disable that disclosure per resource.

## Production readiness

Before deploying to production, follow the [production deployment runbook](docs/deployment-runbook.md), which documents the verified Hostinger Git auto-deployment setup, release preflight, manual dependency/assets/migration steps, post-deployment verification, migration safety and rollback decisions. Use the [production configuration checklist](docs/production-configuration.md) for environment variables, HTTPS/session cookies, database/cache persistence, Resend, administrator bootstrap and logging redaction. For the live service inventory, backup/restore evidence, incident response and outstanding operational verification, see the [production operations runbook](docs/operations-runbook.md).

Run the fail-closed readiness check after configuring the production environment and applying migrations:

```bash
php artisan app:production-readiness
```

The command verifies the production environment, disabled debug mode, HTTPS URL, encryption key, MySQL/MariaDB connectivity, database-backed session/cache tables, encrypted session payloads and secure cookie flags, Resend transactional email, sensitive-log redaction, administrator presence, absence of known development accounts, and removal of temporary bootstrap credentials. It exits non-zero when a check fails and does not print secret values.

Production seeding never creates the predictable-password development accounts or sample educational content. For a new installation, the one-time administrator bootstrap requires temporary `ADMIN_NAME`, `ADMIN_EMAIL`, and a unique `ADMIN_PASSWORD` of at least 16 characters. Remove those values immediately after the first successful bootstrap. Existing demo accounts are not automatically deleted; review them deliberately before considering the production readiness check complete.

## Privacy, data rights and consent

Privacy and legal pages are available at /privacy-policy, /terms and /cookie-policy, with contact and cookie-preference pages linked in the footer. The current policy copy is deliberately labelled DRAFT / NOT LEGALLY APPROVED and excluded from indexing. Do not treat it as an effective legal notice or remove the draft warning until the controller details, processor register and retention schedule are verified and qualified Romanian/EU counsel has approved the text. Configure PRIVACY_CONTACT_EMAIL and record the approved version before launch.

Verified account holders can submit and track access, correction and deletion requests at /privacy/requests. Access requests generate a re-authenticated JSON export. Correction and deletion requests enter an administrator-only review queue at /admin/privacy/requests. Deletion is intentionally not automated: linked assessment, writing, class and progress records have retention and cascade implications that require an approved legal schedule. See docs/privacy/ for the data inventory, retention framework, request SOP, processor register, cookie behaviour and policy version history.

The first-visit cookie banner supports reject, accept and later preference changes. No analytics or advertising script is installed by this repository; the optional analytics preference does not load one. Inspect the production tag manager and injected scripts separately before launch.

## Architecture overview

```
app/
├── Http/Controllers/         # Thin controllers (Home, Resource, Category, Search, Test, Dashboard, Auth)
├── Livewire/Tests/           # Interactive test engine (TakeTest)
├── Livewire/Resources/       # Listening/reading exercise runner
├── Models/                   # Eloquent models (Resource, Test, Question, TestAttempt, …)
├── Services/Assessment/      # Shared weighted scoring and section/topic breakdowns
├── Services/Test/            # Test attempt lifecycle, question-order snapshots and scoring
├── Services/Learning/        # Curriculum progression and listening/reading attempt lifecycle
├── Filament/Resources/       # Admin CRUD for every content and assessment model
└── Filament/Widgets/         # Dashboard widgets (Stats, Popular tests)

database/
├── migrations/               # 0001*users, content, assessment
├── seeders/DatabaseSeeder    # Development/test fixtures; production runs guarded admin bootstrap only
└── factories/                # Resource, Test, Question

resources/views/
├── components/layout.blade.php
├── pages/                    # Public pages
└── livewire/tests/           # Livewire test engine template
```

## Learning engagement and motivation

Learner engagement is derived only from successful server-side learning operations. It is separate from assessment points, percentages and the evidence used by the mastery model.

### XP policy

- Completing a lesson awards **20 XP** once per learner and lesson.
- Completing an adaptive practice session awards **10 XP**, for at most three rewarded sessions per learner per local day.
- Completing a vocabulary review awards **2 XP** once per vocabulary item per learner per local day, for at most ten rewarded items per day.
- XP awards are stored in an immutable ledger with their source activity, timestamp, local activity date and an idempotency key. Retried requests cannot issue a second award.
- Vocabulary and practice limits are reward limits, not restrictions on learning activity. Learners can continue practising after reaching them.

### Daily goals and streaks

Learners choose a daily target of 10, 20, 30 or 50 XP and an IANA timezone (for example, `Europe/Bucharest`). An activity belongs to the calendar date in that timezone at the time the XP is awarded. Each daily record snapshots the target in effect when that date first receives XP, and its completion timestamp is persisted when the target is reached. Streaks count consecutive local calendar dates on which the goal was reached. A streak remains current through the rest of the following local day; after that, it resets to zero. Date-based calculations naturally follow daylight-saving transitions. Changing timezone affects future activity dates; existing daily records are not rewritten.

### Achievements and privacy

Reward values, daily limits, goal choices and achievement definitions are maintained in `config/engagement.php`; awarded points and achievement descriptions are snapshotted so later policy changes do not rewrite history. Achievement awards are persisted once per learner with evidence describing the qualifying milestone. Current milestones cover first lesson, ten lessons, first adaptive practice session, 50 vocabulary-review awards, three- and seven-day goal streaks, and 100/500 XP. XP and achievement records are visible only to their owner.

Global and class leaderboards are intentionally not enabled. The platform does not yet have enough legitimate cohort activity or an explicit consent and privacy model to justify rankings. No assessment scores, writing submissions or private learner histories are exposed through social features.

## Automated tests

```bash
php artisan test
```

Covers public resources, anonymous and authenticated tests, all eight question types in both test and learning runners, structured answer validation and partial credit, weighted and unanswered scoring, empty assessments, rounding, question-order snapshots, expiry, idempotent completion, cross-attempt answer rejection, access and answer-disclosure settings, guest ownership, learning-path enrolment and sequential progression, publication readiness, editorial approval and invalidation, translation fallback and review, media-rights metadata, resource availability, admin relation managers, learning answer disclosure, certificates, learner mastery thresholds and confidence, spaced-repetition scheduling and deduplication, adaptive-practice isolation and answer disclosure, and class-group-scoped teacher progress summaries.

## License

Content and code are shared for free public educational use. See project spec for details.

## Romanian localization

The public site and learner-facing experience use Romanian by default (`APP_LOCALE=ro`) and retain English as the fallback locale (`APP_FALLBACK_LOCALE=en`). Interface strings live in `lang/ro.json`; Laravel validation, authentication, password-reset and pagination messages live in `lang/ro/`.

Published content translations are stored separately from the original English learning material in the polymorphic `content_translations` table. Models using `HasTranslations` expose `translated($field, $locale)` and `translationStatus($field, $locale)`. Only translations marked `reviewed` and editorially `approved`, with a current source hash, are served; missing, pending or stale translations fall back to the source field. The Romanian content seeder is idempotent and runs during deployment migrations as well as database seeding.

English remains intentional in the material learners are expected to practise: reading passages, listening transcripts, vocabulary items, flashcard fronts and backs, question prompts and answer options, and sample writing answers. Writing prompts and the surrounding instructions are localized so learners can understand the task without translating it first.

Run the translation audit before releasing content:

```bash
php artisan translations:audit --locale=ro
```

The command exits non-zero when a published content field is missing a reviewed Romanian translation or its source has changed since review. Public search includes reviewed Romanian translations. The public layout emits Romanian language and social metadata, canonical and `hreflang` links; `/sitemap.xml` lists public content and is referenced by `robots.txt`.

## Writing feedback workflow

See [docs/teacher/class-workflows.md](docs/teacher/class-workflows.md) for teacher onboarding, role boundaries, membership lifecycle, assignment/reporting rules, transfer/archive procedures and privacy treatment. See [docs/writing-feedback-workflow.md](docs/writing-feedback-workflow.md) for the writing submission lifecycle, rubric, learner revisions and retention treatment.

## Speaking practice

- [Speaking practice MVP and decision record](docs/speaking-practice/mvp.md)
- [Speaking practice technology decision](docs/speaking-practice/decision-record.md)
