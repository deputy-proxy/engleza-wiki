# Test Credentials for engleza.wiki

All accounts are pre-seeded by `php artisan migrate:fresh --seed`.

| Role    | Email                    | Password |
|---------|--------------------------|----------|
| Admin   | admin@engleza.wiki       | password |
| Editor  | editor@engleza.wiki      | password |
| Teacher | teacher@engleza.wiki     | password |
| Learner | learner@engleza.wiki     | password |

Admin panel: `/admin` (admin + editor only).
Public site: `/` — anonymous test-taking is enabled.
