# engleza.wiki

> Free knowledge. Better English.

**engleza.wiki** is a free English-learning and assessment platform for learners, teachers, and parents. It combines a public library of learning materials, interactive exercises, CEFR-oriented tests, structured learning paths, learner progress tools, and class-level teacher reporting.

The platform is built around reusable educational content: a resource or assessment can be discovered independently, included in a learning path, and revisited later without duplicating its underlying content or learner history.

## Product capabilities

### Public learning library

Visitors can browse and search resources organised by format, CEFR level, skill, topic, tag, and intended audience.

Supported resource formats:

- **Flashcards** for vocabulary, grammar, and recall practice.
- **Listening exercises** with audio and interactive comprehension questions.
- **Reading exercises** with passages and comprehension questions.
- **Writing activities** with prompts, instructions, word limits, and recommended completion times.

Resources have publication status and discovery metadata. Public pages provide category navigation, related resources, and SEO metadata. Registered learners can save resources to their personal library.

### English assessments

Tests can be taken by guests or registered learners, depending on each test's access setting. Tests can be organised into sections and linked to CEFR levels, skills, and topics.

The assessment experience includes stable per-attempt question assignments and ordering, resumable attempts, server-side scoring, section and topic breakdowns, CEFR estimates, recommendations based on weaker topics, configurable answer explanations, and downloadable certificates for eligible completed attempts.

Assessment history is preserved. Editing a test or its questions does not recalculate historical results against the new content.

### Structured learning paths

Learning paths organise existing content into a guided sequence:

`Learning path → Units → Lessons → Activities`

An activity references an existing resource or test instead of copying it. This keeps the library as the canonical source of learning content while allowing it to be reused in multiple paths.

Learners can enrol in published paths, continue from their personal learning area, resume work in progress, and revisit completed lessons. Lesson order and required activity completion are enforced on the server.

### Learner progress and practice

Registered learners have a dashboard for their learning activity, including tests, resources, writing submissions, path progress, vocabulary reviews, and adaptive practice.

The platform includes:

- **Mastery tracking** based on completed assessment evidence, with confidence thresholds and recency/difficulty weighting.
- **Spaced-repetition vocabulary reviews**, with a review queue and auditable review history.
- **Adaptive practice** that selects questions using mastery evidence, topic priorities, difficulty, and recent exposure.
- **Learning engagement** through XP, daily goals, streaks, and achievements.

These concepts remain distinct: lesson completion records curriculum progress, assessment evidence informs mastery, and XP rewards qualifying learning activity. They are not treated as interchangeable proficiency measures.

### Teacher and class features

Teachers can share class join codes with learners and access results for their own class groups. Class summaries are scoped to completed attempts associated with that class. They do not expose a learner's unrelated private activity, writing submissions, or progress in other classes.

### Romanian interface, English learning material

The public site and learner-facing interface use Romanian by default, with English as the fallback interface locale.

The material learners are expected to practise remains in English: reading passages, listening transcripts, vocabulary, flashcards, question prompts, answer options, and sample writing answers. Supporting instructions and writing task guidance are localised.

Published content translations are stored separately from source content. Only reviewed translations are displayed; missing or draft translations fall back to the original field.

## Current architecture

The application is a Laravel monolith. The public website, learner workflows, assessment engines, and administration panel share one domain model and database. Business rules that must remain consistent across entry points live in application services rather than being duplicated in controllers or UI components.

### Request and presentation layer

- **Laravel routes and controllers** handle public pages, authentication, resource discovery, tests, learning paths, learner dashboards, class results, certificates, search, and the sitemap.
- **Blade** renders the public website and learner-facing pages.
- **Livewire** powers interactive experiences, including the test-taking interface, listening/reading exercise runner, interactive cards, and home-page search.
- **Alpine.js** handles lightweight browser interactions.
- **Filament 5** provides the authenticated administration panel for managing users, educational content, assessments, curriculum, and learning records.

### Domain and application services

| Domain | Responsibility |
| --- | --- |
| Content | Reusable resources, format-specific content, taxonomy, translations, and publication state |
| Assessment | Shared question-response validation and question-level scoring |
| Tests | Test attempt lifecycle, assigned-question snapshots, completion, and test scoring |
| Learning | Listening/reading attempts, curriculum progression, learner progress, vocabulary reviews, adaptive practice, and engagement |
| Classes | Class groups, join codes, and class-scoped teacher results |
| Certificates | Certificate generation for eligible completed test attempts |

The shared assessment engine supports eight question types in both formal tests and interactive listening/reading resources:

- `single_choice`
- `multiple_choice`
- `true_false`
- `text`
- `short_text`
- `matching`
- `ordering`
- `gap_fill`

Structured exercise definitions are validated before publication. Scoring is performed on the server. Multiple-choice, matching, ordering, and gap-fill questions support partial credit; earned points are bounded by each question's configured maximum.

### Persistence and data model

Eloquent models represent the main content and learning domains. The database stores:

- Users and role information.
- Resources and format-specific records for flashcards, listening, reading, and writing.
- Levels, skills, topics, categories, tags, and audiences.
- Tests, sections, questions, options, attempts, and answers.
- Learning paths, units, lessons, activities, enrolments, and learner progress.
- Class groups and their learner/teacher associations.
- Learner vocabulary items and review events.
- Adaptive practice sessions and answers.
- XP awards, daily engagement records, settings, and achievements.
- Reviewed content translations.

Migrations evolve this schema without rewriting historical assessment results. Seeders provide repeatable baseline and example educational content.

### Assessment integrity and learner data

Attempts are owned by the authenticated learner or, for supported guest flows, the originating anonymous session. Assigned question IDs are saved when an attempt starts, so questions added or edited later do not silently change an attempt already in progress.

Scoring and answer disclosure are server-controlled. Correct answers and explanations are withheld during an active attempt and disclosed only after completion, according to the relevant test or resource setting. Expired attempts retain answers saved before expiry but do not qualify for completion certificates. Repeated completion submissions do not create duplicate results.

Learner progress is derived from completed, scored evidence. In-progress, abandoned, and expired attempts are excluded from mastery calculations. Lesson completion is tracked independently. Adaptive practice has its own session and answer records and does not modify formal test attempts.

Vocabulary review scheduling is deterministic and records each review event. Learner-specific vocabulary queues are deduplicated independently of public source content.

Engagement awards are written to an immutable, idempotent ledger. Daily goals and streaks use each learner's configured IANA timezone. XP and achievement records are private to their owner. Global and class leaderboards are not enabled.

### Administration and access control

The Filament panel is available at `/admin`. It provides management interfaces for the platform's content, assessment, curriculum, learner, and class data.

Current roles:

- **Admin**: administrative access, including user and role management.
- **Editor**: administration-panel access and permitted educational content management.
- **Teacher**: teacher-facing class functionality; no administration-panel access.
- **Learner**: learner-facing functionality; no administration-panel access.

User-management authorization is enforced through Laravel policies, not merely by hiding navigation or form controls. The application prevents administrator self-demotion/deletion, protects the last administrator, and blocks deletion of accounts with protected learning or class history. See [Admin panel security](docs/admin-security.md) for the detailed role matrix and account safeguards.

## Technology

| Layer | Technology |
| --- | --- |
| Runtime | PHP 8.3+ |
| Backend framework | Laravel 13 |
| Admin panel | Filament 5 |
| Server-rendered UI | Blade |
| Interactive components | Livewire 4 |
| Lightweight browser interactions | Alpine.js |
| Styling | Tailwind CSS 4 |
| Asset bundling | Vite 8 |
| JavaScript package manager | Yarn 1.22.22 |
| Database | MySQL/MariaDB; SQLite is supported for local/test configurations |
| PDF certificates | laravel-dompdf |

The frontend is built with Vite. The application does not use React, Vue, Inertia, Bootstrap, jQuery, or a third-party component framework.

## Repository layout

```
app/
├── Console/Commands/       # Operational commands, including translation audits
├── Filament/               # Admin panel resources, pages, widgets, and policies
├── Http/Controllers/       # HTTP entry points for public and learner workflows
├── Livewire/               # Interactive search, test, and resource components
├── Models/                 # Eloquent domain models and model concerns
├── Policies/               # Authorization rules
├── Providers/              # Application and Filament panel providers
└── Services/
    ├── Assessment/         # Shared question validation and scoring
    ├── Learning/           # Curriculum, progress, reviews, practice, engagement
    └── Test/               # Test attempt lifecycle, ordering, and scoring

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/                    # Tailwind entry point
├── js/                     # Frontend entry point
└── views/                  # Blade pages, layouts, and Livewire views

routes/                     # Web and console routes
config/                     # Laravel and application configuration
lang/                       # Romanian and English interface translations
docs/                       # Focused operational and security documentation
tests/                      # Automated application tests
```

## Local development

### Requirements

- PHP 8.3 or newer
- Composer 2
- Node.js 22.12+ (or Node.js 20.19+)
- Yarn 1.22.22
- MySQL/MariaDB, or a supported local SQLite configuration

The Composer lockfile targets PHP 8.3. CI validates PHP on 8.4. Vite 8 requires a compatible modern Node.js runtime.

### Install

```bash
composer install
yarn install --frozen-lockfile

cp .env.example .env
php artisan key:generate
```

Configure the database connection in `.env`, then run migrations and seed the development dataset:

```bash
php artisan migrate:fresh --seed
```

Build frontend assets:

```bash
yarn build
```

Start the Laravel development server:

```bash
php artisan serve
```

The application is available at <http://127.0.0.1:8000>.

The development seed includes these accounts, all using the password `password`:

| Role | Email |
| --- | --- |
| Admin | `admin@engleza.wiki` |
| Editor | `editor@engleza.wiki` |
| Teacher | `teacher@engleza.wiki` |
| Learner | `learner@engleza.wiki` |

These are development-only credentials. Do not use them in a deployed environment.

## Validation

Run the full PHP validation suite:

```bash
composer ci:check
```

This runs dependency security auditing, Pint formatting checks, PHPStan/Larastan static analysis, and the Laravel test suite.

Build frontend assets:

```bash
yarn build
```

Run the Laravel tests alone during development:

```bash
php artisan test
```

GitHub Actions runs PHP validation and the frontend build as separate jobs. The PHP job uses PHP 8.4 and MySQL 8.4; the frontend job uses Node 22 and the committed Yarn lockfile.

## Content localization audit

Before publishing translated content, run:

```bash
php artisan translations:audit --locale=ro
```

The command reports published content fields that lack a reviewed Romanian translation or whose source has changed since review. Only reviewed translations are served. The public site also provides canonical and `hreflang` metadata, a sitemap at `/sitemap.xml`, and a `robots.txt` reference to that sitemap.

## License

The project provides free public educational content and software. Refer to the project specification and repository notices for the applicable content and code usage terms.
