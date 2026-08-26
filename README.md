# engleza.wiki

> Free knowledge. Better English.

**engleza.wiki** is a free English-learning knowledge and assessment platform for learners, teachers, and parents. It combines a public educational resource website, a free CEFR-aligned English testing platform, and a comprehensive Filament admin panel.

## Stack

- **PHP 8.3** + **Laravel 13** (framework)
- **Filament 4** (admin panel — Filament 5 not yet released as of Jan 2026; latest available is used)
- **Blade** for public views
- **Livewire 3** for the interactive test engine
- **Alpine.js** for small client interactions (timer, transitions)
- **Tailwind CSS 4** for styling (via Vite)
- **MySQL / MariaDB** for persistence

No React, Vue, Inertia, Bootstrap, jQuery, DaisyUI or other component libraries are used.

## Local development

Prerequisites: PHP 8.3+, Composer 2, Node 20+, Yarn, MySQL/MariaDB.

```bash
# 1. Install dependencies
composer install
yarn install --ignore-engines

# 2. Environment
cp .env.example .env   # (or edit /app/.env directly)
php artisan key:generate

# 3. Database — create db + user
mysql -e "CREATE DATABASE engleza_wiki; CREATE USER 'engleza'@'localhost' IDENTIFIED BY 'engleza_pass'; GRANT ALL ON engleza_wiki.* TO 'engleza'@'localhost'; FLUSH PRIVILEGES;"

# 4. Run migrations + seed the demo content
php artisan migrate:fresh --seed

# 5. Build frontend assets
npx vite build     # (or npx vite for dev with HMR)

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

## Free tests

Anonymous visitors can take free tests without registering. Results are calculated server-side by `App\Services\Test\TestScoringService`, which produces:

- Overall score & percentage
- CEFR level estimate (Pre-A1 → C2)
- Per-skill breakdown (Grammar, Vocabulary, Reading, …)
- Personalised recommendations (Resources filtered by weak topics)

## Architecture overview

```
app/
├── Http/Controllers/         # Thin controllers (Home, Resource, Category, Search, Test, Dashboard, Auth)
├── Livewire/Tests/           # Interactive test engine (TakeTest)
├── Models/                   # Eloquent models (Resource, Test, Question, TestAttempt, …)
├── Services/Test/            # TestScoringService — server-authoritative scoring
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

Covers homepage, resources, tests catalogue, anonymous & authenticated test starts, scoring service, admin auth, and registration.

## Running in the Emergent preview

Supervisor runs three services:

- `mariadb` (local MySQL)
- `frontend` — PHP built-in server on `:3000` serving `public/index.php`
- `backend` — no-op (unused)

Restart the app with `supervisorctl restart frontend` after `.env` changes.

## License

Content and code are shared for free public educational use. See project spec for details.
