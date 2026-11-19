# A1–B2 curriculum authoring and publication

## Status and authority

These maps define the intended scope and sequence for four CEFR learning paths. They are curriculum specifications, not published learner content. Existing seeded/demo resources and grandfathered records are not evidence of teacher review and must not be treated as production-ready material.

A map becomes a learner-facing path only after its lessons, resources and assessments have been authored in the existing Filament CMS, independently reviewed, approved and published. Do not mark a path complete merely because its titles or lesson shells exist.

The current development database has no A1–B2 learning-path records: the seeders create levels and sample resources/tests, but do not create curriculum paths. This is why the learning-path catalogue is empty on a fresh seeded installation. Do not add placeholder/demo paths to production seeding to conceal that gap.

## Learning-path architecture

Create one path for each level: A1, A2, B1 and B2. Each path contains five ordered units. Each unit contains four ordered lessons and a unit assessment checkpoint. The target is 20 complete lessons and five checkpoints per level.

Use the existing curriculum engine:

- Learning path → ordered units → ordered lessons → ordered resource/test activities.
- Lesson objectives, level, skills, topics, publication date and editorial review use existing fields.
- Activities reference canonical resources or tests. Never duplicate learning material into a lesson.
- Existing sequential unlocking is the prerequisite mechanism: a learner completes the earlier available lesson before starting the next one. Do not introduce a second prerequisite engine for this curriculum.
- A unit checkpoint is a published, level-aligned test referenced by a lesson activity.

## Required lesson design

Every lesson must include all of the following in its learner experience:

1. **Measurable objective:** observable language performance, not a topic label.
2. **Input and explanation:** coherent text, audio/video with rights and accessible support, or a teacher-authored explanation.
3. **Worked examples:** natural language demonstrating the target in context.
4. **Guided practice:** supported practice with useful feedback.
5. **Independent practice:** a task that demonstrates the objective without simply repeating the worked example.
6. **Feedback and review:** explained answers, a self-check, or an approved teacher-feedback activity.
7. **Retrieval/recycling:** deliberate reuse of earlier language where appropriate.

A single article link is not a complete lesson. A test alone is not instruction. A lesson's activity sequence must cover input, practice and evidence of learning.

## Unit and path publication gates

Before a path is published:

- All five units exist, are ordered and are published.
- Each unit contains four complete, ordered lessons.
- Every lesson has at least one measurable objective and the correct CEFR level.
- Every lesson has the required instructional and practice sequence.
- Every referenced resource/test is published, accessible, structurally valid and editorially approved.
- Every lesson is editorially approved. Grandfathered means legacy demo content, not reviewed curriculum.
- Each unit has a published assessment checkpoint with a reviewed answer key and level-appropriate blueprint.
- No activity points to draft, archived, pending, revise or otherwise unavailable content.
- Path, units, lessons, activities and assessments have been checked in the learner experience.
- The path has been piloted with teachers and representative learners; revisions are re-reviewed.
- The English source is complete. Romanian translations are optional and must independently pass translation review before they are served.

Existing model publication hooks validate important structural dependencies. They do not establish that instructional design is complete or that content is educationally accurate. Human review remains mandatory.

## Authoring and review sequence

1. Use the level map to create the path and five draft units.
2. Author canonical resources, interactive exercises and tests in the CMS.
3. Complete each lesson's objective, explanatory input, examples, activities and feedback.
4. Link canonical resources/tests to lessons using ordered activities.
5. Run structural checks and review the complete learner journey.
6. Submit new or changed records for editorial review. Do not bypass pending review.
7. Obtain a qualified English-language teacher's approval for every lesson, resource and assessment; obtain a second independent review for core grammar and high-impact CEFR/assessment decisions.
8. Publish approved lessons, then units, then the path.
9. Pilot the path, record feedback, and submit any changes for review again.

## Progression and assessment principles

- The four paths are distinct proficiency bands, not the same content relabelled four times.
- The hardest essential language and task demand determines the level.
- Recycle earlier language while adding new functions and greater independence.
- Assessment checkpoints sample unit outcomes and use a documented blueprint. They do not claim to certify CEFR proficiency unless a validated assessment design supports that claim.
- Lesson completion measures participation in the learning sequence, not mastery. Use the existing progress/mastery system for evidence-based learner progress.

## Level maps

- [A1 curriculum map](a1.md)
- [A2 curriculum map](a2.md)
- [B1 curriculum map](b1.md)
- [B2 curriculum map](b2.md)

## Completion record

For each level, maintain the author, primary teacher reviewer, second reviewer where required, review dates, version, approved path/lesson/test identifiers, pilot cohort/date and unresolved limitations. Never invent reviewer identities or approval dates in repository documentation.
