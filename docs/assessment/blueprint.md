# Indicative English placement assessment blueprint

## Purpose and validity boundary

The A1-B2 forms are short, formative placement screens. They sample grammar, vocabulary and reading comprehension. They do not assess listening, speaking or writing, and they are not CEFR certification exams. A result may be described only as an **indicative estimate** until representative piloting and a documented validation study support stronger claims.

The configured blueprints are engineering targets for item selection and quality checks. They are not a substitute for curriculum mapping, qualified teacher review or empirical validation. A question's `easy`, `medium` or `hard` label is a working editorial judgement, not a calibrated item-difficulty statistic.

## Supported level forms

| Level | Questions | Grammar | Vocabulary | Reading | Difficulty: easy / medium / hard | Time |
|---|---:|---:|---:|---:|---:|---:|
| A1 | 20 | 10 | 6 | 4 | 12 / 8 / 0 | 15 min |
| A2 | 20 | 10 | 6 | 4 | 8 / 8 / 4 | 20 min |
| B1 | 20 | 10 | 6 | 4 | 4 / 10 / 6 | 25 min |
| B2 | 20 | 10 | 6 | 4 | 2 / 8 / 10 | 30 min |

The forms are deliberately limited to three skills. Their section labels describe the skill sampled, not a claim that all communicative abilities at that CEFR level have been measured.

## Focused practice forms

| Form | Questions | Skill | Required topic | Difficulty: easy / medium / hard | Time |
|---|---:|---|---|---:|---:|
| Present Simple grammar | 8 | Grammar | Present Simple | 4 / 4 / 0 | 10 min |
| Past Simple grammar | 8 | Grammar | Past Simple | 3 / 4 / 1 | 10 min |
| Everyday English vocabulary | 8 | Vocabulary | Vocabulary | 4 / 4 / 0 | 12 min |

Focused forms practise one skill/topic and must not be presented as placement assessments. They have separate blueprints and are also checked for prompt reuse against every other formal test.

## Topic coverage targets

- **A1:** present simple, articles, prepositions, everyday vocabulary and short literal reading.
- **A2:** past simple, present continuous, present perfect, common prepositions, everyday vocabulary and short connected reading.
- **B1:** present perfect, conditionals, modal verbs, past continuous, passive voice, reported speech, wider vocabulary and reading for detail/inference.
- **B2:** conditionals, modal verbs, past perfect, passive voice, reported speech, collocations/less common vocabulary and reading for argument/inference.

Every item must carry at least one specific topic tag. Generic tags such as `Grammar` or `Reading` do not replace the specific language feature or reading skill being assessed.

## Form construction rules

1. Each form must meet its question count, section count, difficulty distribution and time target.
2. Each form must use a sufficiently large, level-specific item pool. A small shared pool copied into several tests is not independent evidence and is flagged as reuse.
3. Do not repeat a normalized prompt within a form or across distinct forms unless an explicitly documented equating design requires it.
4. Keep correct answer positions reasonably balanced in single-choice forms. Do not distort the content merely to force a pattern.
5. Do not add listening items until the audio source, rights, transcript/accessibility support, task blueprint and answer validation are ready.
6. Formal test attempts and scoring remain separate from adaptive practice sessions. Do not use practice selection or practice evidence to alter a completed formal result.

## Automated checks

Run `php artisan assessment:bank-audit` to inspect content created under the prospective editorial workflow. Existing grandfathered demo tests are excluded by default.

Run `php artisan assessment:bank-audit --include-grandfathered` only when a diagnostic inventory of legacy/demo records is needed. This mode reports structural and reuse risks; it does not edit records, approve content or turn demo content into production-ready material. A non-zero exit code means one or more blocking checks failed.

The audit checks question-type inventory, duplicate prompts within/across forms, supported question definitions and option structure, missing explanations/topic tags/CEFR rationales, question count, section/skill coverage, difficulty distribution, required topic coverage, level assignment, timing and correct-option position balance. JSON output includes each item's prompt, level, skill, tags, type, difficulty, option/key positions, explanation/rationale presence, form review metadata and cross-form reuse.

## Current status

The configured A1-B2 blueprints are targets. The existing seeded item bank is demo material and is not teacher-validated. Current item reuse, missing explanations/tags and blueprint gaps must be treated as findings to resolve, not as approved content. No representative learner pilot or psychometric analysis has been completed; there are therefore no empirical difficulty/discrimination results and no validity claim beyond an indicative practice estimate.
