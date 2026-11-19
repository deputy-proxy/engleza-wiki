# engleza.wiki personal-data inventory

**Status:** engineering inventory, 2026-11-18. This is not a completed GDPR Article 30 record. The controller and qualified Romanian/EU counsel must validate purposes, legal bases, recipients, international transfers and retention periods before approval.

| Data flow | Current data and storage | Purpose / access | Retention decision |
|---|---|---|---|
| Accounts | users: name, email, password hash, role, verification timestamp, remember token | Authentication and account operation; owner and administrators | Account closure and statutory retention require approval |
| Authentication | password_reset_tokens; sessions: session ID, optional user ID, IP, user-agent, encrypted payload, last activity; cache/rate-limit keys | Authentication, reset, security; application and hosting operators | Session expiry configured; verify cleanup and backup/log expiry |
| Formal assessments | test_attempts, test_answers, test_attempt_events, assessment_results | Test delivery, scoring, progress, certificates; learner sees own records, teacher sees only their class, administrators | No approved record-level period; no automatic deletion |
| Listening/reading | learning_attempts, learning_answers | Exercise delivery and progress; learner/session owner and administrators | No approved period |
| Classes | class_groups, class_group_memberships, class_group_transfers, class assignments and class-linked attempts | Cohort management; owning teacher and administrators see active members only; learners see their own membership | Membership and transfer events are retained; archive classes and transfer ownership rather than deleting history |
| Writing | writing_submissions: current text, word count, status, timestamps and optional class group; writing_submission_versions: immutable text/version snapshots; writing_submission_reviews: teacher rubric scores, feedback, reviewer and publication timestamp | Writing practice and teacher feedback; learner sees own records and published feedback, owning class teacher sees only that class's assigned submissions, administrators can review | High-sensitivity educational text and educational feedback; no approved record-level period or automatic deletion |
| Learning paths | learning_path_enrollments, lesson_progress, lesson_activity_progress | Enrolment and progression; learner owner and administrators | No approved period |
| Adaptive practice | adaptive_practice_sessions, adaptive_practice_answers | Personalised practice; learner owner and administrators | No approved period |
| Vocabulary | learner_vocabulary_items, vocabulary_review_events | Personal spaced repetition; learner owner and administrators | No approved period |
| Engagement | learner_engagement_settings, learner_daily_engagements, experience_point_awards, learner_achievements | Goals, streaks, XP and achievements; learner owner and administrators | No approved period |
| Saved content | saved_resources | Personal saved-resource list; learner owner and administrators | No approved period |
| Content and translations | Resources, tests, questions, content_translations, author references | Public educational publishing; public content and editor/admin authoring | Review personal data accidentally embedded in authored text |
| Media | Listening-resource media paths on configured public storage disk | Audio delivery; public retrieval where referenced, editor/admin management | Verify location, access logs, backup and personal-data presence |
| Logs | Laravel logs and exception context | Diagnostics and security; authorised operators | Sensitive context redacted; verify rotation, retention and hosting access |
| Privacy requests | privacy_data_requests: type, description, status, reviewer, internal notes, identity verification and completion timestamps | Track access/correction/deletion requests; owner sees own request without notes, administrators process | Keep only as long as needed to demonstrate handling; counsel to approve |
| Cookie choice | Encrypted HttpOnly engleza_cookie_preferences cookie: analytics choice, policy version, update time | Remember optional-cookie choice; browser/application | 180 days |

## Minimisation and access controls implemented

- New test and listening/reading attempts no longer persist IP addresses or user-agent fingerprints. Historical values remain until an approved retention decision.
- The cookie preference is encrypted, HttpOnly, SameSite=Lax and follows the session Secure setting.
- No analytics or advertising tag is installed by this repository. The optional analytics preference does not load a script.
- Writing submissions, test attempts, learning attempts, assessment results, class groups, user records and privacy requests are admin-only in Filament. Teacher workspace, class-result, roster, assignment and writing-review access remains scoped to classes currently owned by that teacher and active members only.
- Personal-data export excludes password hashes, remember tokens, raw session payloads, internal staff notes and other learners' records.

## Evidence still required

1. Controller legal identity, establishment address, privacy contact and DPO/contact role if applicable.
2. Hostinger DPA, hosting region, CDN processing, backup schedule/retention, support access and subprocessors.
3. Configured SMTP provider and its DPA, region, message retention and subprocessors.
4. Bunny Fonts processing terms and whether self-hosting is preferable.
5. Production log destination, access, rotation and backup inclusion.
6. Operator-managed exports and copies.
7. Counsel approval of purposes, legal bases, retention, child-user considerations, international transfers and policy wording.
