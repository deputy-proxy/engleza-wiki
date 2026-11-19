# Teacher and class-group workflows

## Role and permission matrix

| Capability | Administrator | Teacher | Learner | Editor |
|---|---|---|---|---|
| Open `/teaching` | All classes | Own classes only | No | No |
| Create a class | Yes; assigns a teacher | Yes; owns the new class | No | No |
| Edit class details and assignments | Any class | Own classes only | No | No |
| Add learners | Any class | Own classes only | No | No |
| Join through invite code | No | No | Own account only | No |
| Remove a learner | Any class | Own classes only | No | No |
| View roster and class progress | Any class | Own classes only | Own work only | No |
| Review submitted class writing | Any class | Own classes and active members only | Own feedback only | No |
| Transfer class ownership | Any class | Own classes only | No | No |
| Archive or reopen a class | Any class | Own classes only | No | No |
| Filament admin panel | Yes | No | No | Yes, subject to resource permissions |

An administrator grants the teacher role through user management. Teachers use the authenticated web workspace and do not receive general Filament access. If a teacher's role is revoked, transfer their classes first. Teacher access is checked against the current `class_groups.teacher_id` on every request, so transfer immediately revokes the previous teacher's access.

## Teacher onboarding and class creation

1. An administrator creates/verifies the account and assigns the `teacher` role.
2. The teacher signs in and opens **Teaching** in the main navigation or visits `/teaching`.
3. The teacher creates a class. The application generates a unique join code and invite URL. Administrators can create a class and assign it to a teacher.
4. The teacher can share the invite URL/code or add an existing learner account by email. The add-by-email action does not send an email; sharing the invite URL is the invitation mechanism.
5. The teacher assigns only published, public tests and writing tasks with an explicit editorial approval decision. Grandfathered/demo content is not assignable.
6. The teacher uses the class workspace for roster management, progress, recent assessment activity, recent writing submissions and full class reports.

## Membership lifecycle and audit

- A learner follows `/classes/{code}`, signs in and chooses **Join class**. Only accounts with the `learner` role can join.
- A teacher can add an existing learner by email from the class roster. Unknown addresses and non-learner accounts are rejected.
- Repeated join/add requests are idempotent while a membership is active.
- Removing a learner sets `removed_at` and `removed_by`; it does not delete the membership record or the learner's work.
- Rejoining after removal creates a new membership event. Earlier membership events remain in the audit history.
- Existing class-linked attempts and writing submissions are backfilled as historical memberships when this feature is deployed, so current learners do not lose access to existing class evidence.
- Class groups are archived, not deleted. Archiving prevents new joins, class-linked test attempts and class writing submissions. The owner can reopen the class.

Membership history is stored in `class_group_memberships`. `added_by`, `joined_at`, `removed_by` and `removed_at` record who added or removed a learner and when. Ownership changes are recorded in `class_group_transfers` with previous teacher, new teacher, actor and timestamp. Membership and transfer audit records cannot be deleted through application models. Foreign keys preserve their parent class and referenced user accounts.

## Assignments, reporting and learner-data boundaries

- Teachers assign existing tests and writing resources. The application does not create a duplicate assignment engine. Class-level learning-path assignment is not supported by the current data model.
- A class assessment must be the test assigned to that class and the learner must be an active member when starting it. Supplying a class code without an active membership is rejected.
- Class writing submissions require an active membership and an assignment attached to that class. Teacher lists, detail pages and all review/mutation endpoints independently recheck active membership.
- Only resources with `status=published` and `editorial_status=approved` are offered for class assignment. `grandfathered` demo content is deliberately excluded.
- Teacher reports include only registered, currently active members of that class. Anonymous attempts and attempts without a current membership are not shown in the roster or reports.
- Progress summaries use completed assessment evidence attached to that class. They do not aggregate unrelated learner history, other classes, adaptive-practice history, vocabulary activity or private learning-path progress.
- Teachers see learner display names and class-specific progress, not learner email addresses or unrelated profile data. Removed learners disappear from current reports and writing review. Their educational records remain stored; the learner can still access their own history.
- No bulk membership deletion action is exposed. Removal is an auditable event, not destructive deletion.

## Ownership transfer and archive

1. The current teacher or an administrator selects another account with the `teacher` role.
2. The application records the previous teacher, new teacher, actor and timestamp in `class_group_transfers`, then changes `class_groups.teacher_id` in the same transaction.
3. The previous teacher immediately loses access to the workspace, roster, reports and writing submissions. The new teacher gains access.
4. Memberships, assignments and learner history remain attached to the class.
5. Archive the class to stop new joins and submissions. Reopening restores normal class access. Do not delete class groups or membership/transfer history.

## Privacy and data rights

Class membership, additions/removals, ownership transfers, class-linked attempts and writing submissions are personal data or educational records. Teachers receive only class-scoped information. Removing a learner changes current teacher access; it does not erase historical records. Class deletion and membership-history deletion are blocked where they would destroy audit or educational history. Retention periods remain proposed and require controller and qualified Romanian/EU counsel approval before destructive automation.

Personal data exports include a learner's own membership history and a teacher's current-class membership events, membership actions and transfer history. Teacher exports omit other learners' identifiers. Teacher writing reviews are exported only for currently owned classes and active members; former classes are not included after transfer. See [data inventory](../privacy/data-inventory.md), [retention schedule](../privacy/retention-schedule.md) and [data-subject request SOP](../privacy/data-subject-request-sop.md).

## Verification

Run the focused feature suite:

```bash
php artisan test --compact tests/Feature/TeacherClassWorkflowTest.php tests/Feature/WritingSubmissionWorkflowTest.php tests/Feature/LearnerProgressTest.php
```

The tests cover role and ownership boundaries, class creation, assignment eligibility, explicit self-join, idempotent add/join, removal history, report filtering, assigned-test enforcement, transfer audit/access revocation, archive/reopen, privacy exports and removed-learner writing-review IDOR paths.
