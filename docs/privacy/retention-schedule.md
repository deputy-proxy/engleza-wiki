# Data retention and deletion schedule

**Status: proposed control framework, not an approved legal schedule.** Exact periods must be approved by the controller and qualified Romanian/EU counsel before destructive automation is enabled.

| Record group | Current behaviour | Proposed handling for approval | Destructive action enabled? |
|---|---|---|---|
| Account profile | Retained until manually managed | Delete or anonymise after verified closure unless a documented legal reason requires retention | No |
| Password reset tokens | Laravel broker expiry/cleanup configuration | Keep only for configured short reset window | Existing framework behaviour |
| Sessions | Laravel session lifetime and cleanup | Expire inactive sessions; verify production scheduler and database cleanup | No new job in this issue |
| Tests, answers and results | Persist; some content FKs cascade; user references may null on delete | Decide retention by educational purpose, certificate/appeal needs and legal obligations | No |
| Learning attempts and answers | Persist; user reference nullable | Same as tests; separately review technical identifiers | No |
| Writing submissions | Persist as learner text; FK to user cascades on delete | Purpose-limited retention with review window; check class/appeal/legal needs | No |
| Class groups and results | Teacher owns group; deleting teacher currently cascades group deletion | Transfer ownership or anonymise group/results before closure | No |
| Progress, vocabulary and engagement | Persist as account-owned history | Delete or anonymise with closure, subject to approved exceptions | No |
| Privacy requests | Request record retained; after approved account closure, requester link, description and internal notes are cleared | Keep minimal anonymised accountability record for counsel-approved period | No |
| Logs and backups | Host/application retention not fully verified | Set explicit rotation, access and backup expiry | No |

## Safety rules

- Do not run migrate:fresh, mass deletes or account-deletion scripts against production.
- Do not rely on ON DELETE CASCADE as the deletion policy. Cascades can destroy writing, adaptive-practice answers, vocabulary history, XP and class records.
- A deletion request is a review workflow, not an automatic delete command. Verify identity, linked records, class relationships, certificates, legal holds, backups and processor copies.
- Use irreversible anonymisation only after confirming no direct or reasonably linkable identifier remains. Pseudonymisation is not anonymisation.
- Record approval, executor, scope, date, exceptions and backup expiry.
- Do not mark a request complete solely because the active database row was removed while backups or processor copies remain.
