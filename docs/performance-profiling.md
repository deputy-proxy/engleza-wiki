# Performance profiling runbook

This runbook covers the learner and content flows tracked by issue #29. Profile against the same database engine and PHP/runtime versions as the target environment; do not compare SQLite results with MySQL production results.

## Flows and current guardrails

| Flow | Entry point | Current data guardrail / query behavior |
| --- | --- | --- |
| Content search | `SearchController::index` (`GET /search`) | Resource results capped at 30; test results capped at 10. Search uses substring `LIKE` predicates over base fields and reviewed translations. |
| Resource catalogue | Resource catalogue routes/controllers | Re-profile when catalogue filters or eager-load graphs change; inspect pagination and relationship loading before adding indexes. |
| Learner dashboard | `DashboardController::index` (`GET /dashboard`) | Recent formal attempts capped at 10; saved resources capped at 6. Progress summary still considers all completed evidence to preserve mastery semantics. |
| Learner progress | `LearnerProgressService::summary` | Completed test, learning and adaptive evidence is intentionally unbounded by history because truncation changes mastery. Recent activity is limited to the newest 8 merged events. Enrolled-path lesson, activity, resource, test-section and question-validation relations are preloaded for the whole path; the summary uses those loaded relations rather than reloading them per lesson. Recommendations batch recent-resource history, scan candidate resources in chunks of 100 and stop once each weak topic has a candidate. |
| Vocabulary reviews | `VocabularyReviewController::index` (`GET /reviews`) | Due and upcoming lists are each capped at 30; due count is a separate aggregate. |
| Adaptive practice | `AdaptivePracticeController::show` (`GET /practice/{session}`) | Questions are limited by the session's stored question order; session lookup is user-scoped. |
| Teacher summary | `LearnerProgressService::classGroupSummary` | Attempts are scoped to the class group and active membership; historical completed attempts remain included in the aggregate. |

## Recorded local measurements

Environment: repository development Sandbox, PHP 8.5.4, Laravel 13.42.0, MySQL configured by `.env.testing`. These are local regression measurements, not production latency SLOs.

- Learner progress feature tests after recommendation batching and 10-lesson eager-loading regression coverage: 9 tests, 101 assertions, 17.22 seconds.
- Full Laravel suite including the profiling harness: 206 tests, 1,884 assertions, 461.15 seconds.
- Seeded route/service baseline (`PerformanceBaselineTest`): 20 seeded resources (4 published), one learner with one completed assessment attempt, one teacher class and one adaptive session. Query count, Laravel request/service duration and PHP peak-memory delta were:

  | Flow | SQL statements | Duration | Peak-memory delta |
  | --- | ---: | ---: | ---: |
  | Search | 10 | 20.88 ms | 0.63 MB |
  | Resource catalogue | 19 | 25.46 ms | 0.83 MB |
  | Learner dashboard | 34 | 72.13 ms | 0.97 MB |
  | Vocabulary reviews | 3 | 9.27 ms | 0.48 MB |
  | Adaptive practice | 12 | 20.66 ms | 0.54 MB |
  | Teacher summary service | 11 | 8.17 ms | 0.13 MB |

  The slowest SQL was the published-resource lookup for search (4.33 ms); teacher-summary's main attempt lookup was 0.77 ms. Run with `PERFORMANCE_PROFILE_OUTPUT=1 php artisan test --compact --filter=PerformanceBaselineTest`. The test enforces query-count budgets for each flow. These timings are single local feature requests or service invocations (teacher summary is measured at the service boundary), not median/p95 or production measurements.

- Search and resource catalogue scale fixture: 10, 100 and 1,000 additional published resources, plus the four seeded published resources. Search remained at 10 SQL statements and catalogue at 19 across all sizes:

  | Additional resources | Search queries / ms / peak memory | Catalogue queries / ms / peak memory |
  | ---: | ---: | ---: |
  | 10 | 10 / 111.46 ms / 2.18 MB | 19 / 34.07 ms / 0.92 MB |
  | 100 | 10 / 13.85 ms / 0.53 MB | 19 / 21.95 ms / 0.83 MB |
  | 1,000 | 10 / 20.78 ms / 0.53 MB | 19 / 32.79 ms / 0.83 MB |

  The first search run includes cold-process overhead. At 1,000 additional resources, the slowest search SQL was the published-resource lookup (1.34 ms); catalogue's slowest statement was the skills lookup (2.30 ms). Run with `PERFORMANCE_PROFILE_OUTPUT=1 php artisan test --compact --filter=test_search_and_catalogue_keep_bounded_query_counts_at_increasing_resource_sizes`.

- Progress-history scale fixture: 10, 100 and 1,000 completed test attempts, each with one scored answer. Query count remained at 20 for every size; duration and peak memory were 41.47 ms / 1.15 MB, 39.96 ms / 0.95 MB and 315.93 ms / 13.81 MB respectively. At 1,000 attempts, the eager-loaded `test_answers` query was slowest at 19.31 ms. Run with `PERFORMANCE_PROFILE_OUTPUT=1 php artisan test --compact --filter=test_progress_summary_query_count_is_stable_at_10_100_and_1000_completed_attempts`.

- Ten-lesson path fixture: the pre-change implementation issued 74 SQL statements for `LearnerProgressService::summary`; the eager-loaded implementation issues 27 (63.5% fewer) while retaining all 10 available lessons. The regression budget is 40 statements.
- Three-topic recommendation fixture: the pre-change implementation issued 10 SQL statements; the batched implementation issues 7 (30% fewer) and preserves the same recommended resource per topic.
- Recommendation query regression fixture with two weak topics: 7 SQL statements for three weak topics, recent-history lookup, chunked candidate resources, vocabulary due check and required eager loads, down from 10 with the pre-change implementation (30% fewer statements). The regression test enforces a maximum of 7 statements for three weak topics.
- Frontend unit tests: 3 passed. Production Vite build completed successfully.
- Browser end-to-end suite against MySQL 8.4: 21 passed in 48.2 seconds, including search/resource discovery, dashboard, vocabulary/adaptive learner journeys, teacher workspace, learning-path progression and WCAG checks.
- Pint: 323 files passed. PHPStan: no errors.

Feature-test duration includes database setup, factories, editorial approval and assertions; it is not an HTTP response-time measurement. Do not treat it as one. These are local synthetic-data measurements, not production SLOs or p95 estimates. A staging run is still required to confirm results against real production data distribution, warm-request medians/p95, and actual table cardinality before deployment.

## Query/index review

Existing indexes already cover the principal learner filters:

- `test_attempts (user_id, status, completed_at)`;
- `learning_attempts (user_id, status, completed_at)`;
- `lesson_progress (user_id, status)`;
- `learner_vocabulary_items (user_id, due_at, id)`;
- `learning_path_enrollments (user_id, activated_at)`.

Issue #29 adds `adaptive_practice_sessions (user_id, status, completed_at)`. The previous adaptive index ended in `started_at`, while progress aggregation filters completed sessions by `completed_at`; the new index matches that predicate and ordering. Validate the migration and inspect `EXPLAIN` on the production MySQL major version before release. A local MySQL 8.4 `EXPLAIN` for the completed-session filter selected `adaptive_sessions_learner_progress_idx` (`range`, index condition, backward index scan). The local test database contained zero adaptive sessions, so this confirms index eligibility, not production selectivity. The migration adds an index only and does not rewrite learner data. Its production build time and lock impact have not been measured against production table cardinality because production database access is not part of this task. Before deployment, record `adaptive_practice_sessions` row count and test the index build on a production-sized staging copy; confirm the chosen MySQL online-DDL behavior and maintenance window.

No full-text index is introduced. Search currently uses leading-wildcard substring matching, which ordinary B-tree indexes cannot accelerate; a full-text change needs relevance tests and measured evidence before changing search semantics. No progress cache or materialized summary is introduced: recency weights depend on the current time, and evidence changes when attempts, question-topic assignments, translations, resources, vocabulary, enrollments or published lesson content change. A cache would need explicit per-user isolation and invalidation across all of those write paths.

## Repeatable profiling procedure

1. Use a dedicated MySQL database on the same major version as production and run the current migrations.
2. Build small, medium and large fixtures (suggested scales: 10, 100 and 1,000 completed attempts per learner; 10, 100 and 1,000 published resources; and 1, 10 and 50 lessons per enrolled path). Keep fixture composition constant between revisions.
3. For each flow above, record route response time (median and p95 over at least 30 warm requests), SQL statement count, slowest SQL statements, peak PHP memory, returned row counts and fixture cardinalities.
4. Capture SQL with a temporary `DB::listen` listener or the database's slow-query log. Keep instrumentation out of production request paths. Run `EXPLAIN` for the slowest MySQL statements and record chosen indexes and estimated rows.
5. Compare before/after on the same machine, database, fixtures, locale and cache state. Report cold and warm results separately.
6. Add a regression test only for stable query budgets or correctness invariants. Avoid wall-clock assertions in CI because shared runners do not provide reliable latency guarantees.

Example focused checks:

```bash
PERFORMANCE_PROFILE_OUTPUT=1 php artisan test --compact --filter=PerformanceBaselineTest
php artisan test --compact --filter=LearnerProgressTest
composer lint:check
composer types:check
yarn test
yarn build
```

The complete CI workflow remains the release gate. Re-run the profile after changes to progress aggregation, search predicates, dashboard eager loading, review pagination, adaptive session retrieval or teacher summaries.
