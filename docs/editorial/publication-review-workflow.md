# Publication and editorial review workflow

## Authoring

1. Create or edit the resource, test, lesson, activity or translation in the existing Filament admin panel.
2. Add the objective, CEFR level, skill/topic tags, instructions, examples, practice, answer key and metadata required by the content type.
3. For media, record the source, licence/permission and attribution before upload. Do not publish an asset with unclear rights.
4. Save the content. New or changed material is `pending`; it is not served through public resource/test/lesson routes, public search or the sitemap.

## Review

1. Open the item in the existing Content admin area and inspect the complete public learner experience.
2. Apply `docs/editorial/standards.md` and `docs/editorial/cefr-content-standards.md`.
3. Verify every answer key against the prompt and options. A qualified teacher must approve scored educational material; core grammar and CEFR placement require a second reviewer.
4. Review Romanian translations for teaching meaning and confirm that pending/stale translations fall back to the unchanged English source.
5. Record a decision using the editorial review actions: approve, request revision, unpublish, noindex or archive/remove.
6. The CMS stores reviewer, decision timestamp and notes. Approval makes the item eligible for public display.

## Change control

- Editing a reviewed parent item returns it to `pending`.
- Editing/deleting a question, option, section, interactive child item, lesson activity or translation invalidates its parent review.
- A translation is served only when its translation status is `reviewed`, its editorial status is `approved`, and its source hash matches the current source.
- A `noindex` resource may remain accessible to learners but is excluded from the sitemap and receives a noindex directive.
- `revise` and `pending` content is hidden; `unpublish` moves it to draft; `remove` archives it.

## Scope of automated checks

The editorial workflow is prospective. Existing demo content is grandfathered and remains available without being marked as editorially approved. It is not retroactively audited, modified or blocked. New content created after the editorial workflow rollout enters review automatically. The audit commands, when run manually, only include records created after the rollout timestamp; they do not inventory legacy demo content.

