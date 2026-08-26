# engleza.wiki — Product Requirements Document

## Original problem statement
Build a production-ready Laravel 13 + Filament application called engleza.wiki — a free
English-learning knowledge and assessment platform. Public site + free CEFR test engine +
Filament admin panel. Stack constraints: PHP, Laravel 13, Filament (5→latest 4), Blade,
Tailwind CSS 4, Livewire 3, Alpine.js, MySQL. No React/Vue/Inertia/Bootstrap.

## Personas
- Learner (default role)
- Teacher (accesses resources tagged for teachers)
- Parent (browses learner-oriented content)
- Editor & Admin (manage content through Filament)

## Architecture (V1)
- Content models: Resource, Category (hierarchical), Tag, Level, Skill, Audience, Topic (hierarchical), Media
- Assessment models: Test, TestSection, Question, QuestionOption, TestAttempt, TestAnswer, AssessmentResult, TestAttemptEvent
- User model: role enum (admin/editor/teacher/learner)
- Public routes: home, resources index/show, categories/{slug}, search, tests index/show/start/take/result, dashboard
- Livewire engine at `App\Livewire\Tests\TakeTest` — server-authoritative scoring
- Filament panel at /admin with navigation groups: Content, Assessments, Users, System
- Widgets: StatsOverview, MostPopularTests
- Seeders provide 4 users, 7 CEFR levels, 8 skills, 7 audiences, 20 topics, 20 resources, 7 tests, 100+ questions

## What's been implemented (Jan 2026)
- Laravel 13 install + PHP 8.3 + MariaDB running under supervisor
- Full DB schema (24 migrations consolidated into 2 files)
- All Eloquent models + relationships + policies-by-role
- Homepage with visual layout matching provided landing image (navy/blue/white, pastel category cards)
- Public routes: resources index/show, categories, search, tests catalogue, test detail, about
- Livewire test engine with progress bar, timer, question nav, autosave
- TestScoringService computing overall %, CEFR level, per-skill breakdown, and topic-based resource recommendations
- Auth (email/password) with `learner` default role
- User dashboard, my-tests, my-resources, save-resource toggle
- Filament admin for 12 resources (Resource, Category, Tag, Level, Skill, Audience, Topic, Test, Question, TestAttempt, AssessmentResult, User) with navigation groups
- Admin dashboard with 5 stat widgets + popular content widget
- SEO metadata in layout component (title/description/OG/Twitter)
- Rate limiting on login/register/start test
- CSRF protection on all POST routes
- Automated feature tests (PublicFlowTest — 12 tests covering critical paths)
- Comprehensive seed data with realistic content
- README + test_credentials.md

## Backlog (P1/P2)
- P1: SEO sitemap.xml + robots.txt
- P1: Downloadable media file uploads through Filament (Media model is ready)
- P1: Filament relation manager for TestSection→Questions & Question→Options
- P2: LearningPath, UserProgress, Certificates
- P2: Additional question types wired up in Livewire (multiple_choice/true_false/text)
- P2: Elasticsearch/Meilisearch adapter behind SearchController
- P2: Dark mode, print stylesheet, PDF export of results

## Iteration 2 — Interactive Learning Resources (Jan 2026)

Added 4 new interactive resource types, natively integrated with the existing Resource model:

- **Cards** — CardResource + ResourceCard models, Livewire flashcard runner with 3D flip via Alpine, keyboard-accessible
- **Listening** — ListeningResource with native HTML5 audio/video, Filament upload (mp3/mp4, configurable max size via engleza.max_media_kb)
- **Reading** — ReadingResource with two-column desktop layout, Livewire exercise runner
- **Writing** — WritingResource + WritingSubmission with live Alpine word counter, submit + draft modes
- LearningAttempt / LearningAnswer models power listening & reading scoring (parallel to TestAttempt)
- Questions table extended with nullable listening_resource_id + reading_resource_id (test_section_id also made nullable) — backwards compatible
- Resource show route dispatches to per-type Blade views under resources/types/*
- Resource catalogue: new type filter + coloured badges (CARDS/LISTENING/READING/WRITING)
- Filament Resource form now shows type-specific sections (card/listening/reading/writing) via reactive visibility; CardsRelationManager for editing individual cards
- New dashboard section: My Writing
- Seeder InteractiveResourcesSeeder adds 3 card resources (40 cards), 2 listening, 3 reading (15 Q), 4 writing tasks
- 8 new automated tests (`InteractiveResourcesTest`) — 22 total passing

## Files affected
- migrations/2026_01_02_000001_create_interactive_resource_tables.php
- app/Models/{CardResource, ResourceCard, ListeningResource, ReadingResource, WritingResource, WritingSubmission, LearningAttempt, LearningAnswer}.php
- app/Livewire/Resources/{InteractiveCards, ExerciseRunner}.php
- app/Http/Controllers/LearningController.php
- app/Services/Learning/LearningScoringService.php
- app/Filament/Resources/Resources/{Schemas/ResourceForm, Pages/*, Support/NestedTypeContent, RelationManagers/CardsRelationManager}.php
- resources/views/pages/resources/types/{card,listening,reading,writing,result}.blade.php
- resources/views/livewire/resources/{interactive-cards,exercise-runner}.blade.php
- resources/views/pages/dashboard/writing.blade.php
- database/seeders/InteractiveResourcesSeeder.php
- tests/Feature/InteractiveResourcesTest.php
