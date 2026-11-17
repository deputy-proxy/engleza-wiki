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
- Development credentials (all with password `password`):

| Role     | Email                       | Password  |
|----------|-----------------------------|-----------|
| Admin    | admin@engleza.wiki          | password  |
| Editor   | editor@engleza.wiki         | password  |
| Teacher  | teacher@engleza.wiki        | password  |
| Learner  | learner@engleza.wiki        | password  |

Only `admin` and `editor` roles can access `/admin`.

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

### Deployment note

Run `php artisan migrate --force` during deployment. The new attempt metadata and score-breakdown fields are nullable, and existing scores are not rewritten or backfilled. Historical attempts without a saved question set retain their existing score but do not receive a reconstructed question review. Existing listening/reading resources default to showing answer explanations after completion, preserving the previous behaviour; the new toggle can disable that disclosure per resource.

## Architecture overview

```
app/
├── Http/Controllers/         # Thin controllers (Home, Resource, Category, Search, Test, Dashboard, Auth)
├── Livewire/Tests/           # Interactive test engine (TakeTest)
├── Livewire/Resources/       # Listening/reading exercise runner
├── Models/                   # Eloquent models (Resource, Test, Question, TestAttempt, …)
├── Services/Assessment/      # Shared weighted scoring and section/topic breakdowns
├── Services/Test/            # Test attempt lifecycle, question-order snapshots and scoring
├── Services/Learning/        # Shared listening/reading attempt lifecycle and scoring
├── Filament/Resources/       # Admin CRUD for every content and assessment model
└── Filament/Widgets/         # Dashboard widgets (Stats, Popular tests)

database/
├── migrations/               # 0001*users, content, assessment
├── seeders/DatabaseSeeder    # Full demo dataset (20 resources, 7 tests, 100+ questions)
└── factories/                # Resource, Test, Question

resources/views/
├── components/layout.blade.php
├── pages/                    # Public pages
└── livewire/tests/           # Livewire test engine template
```

## Automated tests

```bash
php artisan test
```

Covers public resources, anonymous and authenticated tests, weighted and unanswered scoring, empty assessments, rounding, question-order snapshots, expiry, idempotent completion, cross-attempt answer rejection, access and answer-disclosure settings, guest ownership, learning controller/Livewire parity, learning answer disclosure, certificates and teacher/admin class reporting.

## Running in the Emergent preview

Supervisor runs three services:

- `mariadb` (local MySQL)
- `frontend` — PHP built-in server on `:3000` serving `public/index.php`
- `backend` — no-op (unused)

Restart the app with `supervisorctl restart frontend` after `.env` changes.

## License

Content and code are shared for free public educational use. See project spec for details.
