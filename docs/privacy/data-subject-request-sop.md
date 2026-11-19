# Data-subject request handling SOP

**Owner:** controller to appoint. **Status:** operational draft pending legal review.

## Request channels and identity

- Account holders use /privacy/requests after authentication and email verification.
- In-product requests and exports require the current account password.
- For representatives, child/parent requests or disputed identity, pause and follow a counsel-approved supplementary verification process. Do not collect excessive identity documents.
- Record verification time and method, never the password or unnecessary identity-document copies.

## Access and export

The application generates a JSON export after re-authentication. It includes the account profile and account-owned learning, assessment, writing submissions, immutable writing versions, published teacher feedback, teacher-authored feedback records, class assignments, the account holder’s own class membership history, teacher-owned class membership events, membership actions and ownership-transfer history, class-teacher, vocabulary, progress, engagement and request records. Teacher exports omit other learners’ identifiers. It excludes authentication secrets, raw session payloads, internal staff notes, other learners' records, server logs, infrastructure backups and processor-held copies. Recheck scope whenever new personal-data tables or processors are added.

## Rectification

Record the exact fields and requested correction. Verify the source of truth and preserve audit evidence where required. Do not rewrite historical assessment answers/results without a documented correction policy. Record the outcome and notify the requester through the approved channel.

## Erasure and account closure

Do not perform automatic deletion. Map tests/answers/results, certificates, writing, class groups, the account holder's own membership history, teacher-owned class membership events, membership actions and ownership-transfer history, progress, vocabulary, XP, logs, exports, backups and processors. Identify statutory retention, disputes, safeguarding and educational-record obligations with counsel. Decide record-by-record whether to delete, irreversibly anonymise, restrict or retain under a documented exception. Transfer teacher-owned classes before closure where appropriate. Membership removals and class transfers are retained as audit history; removing a learner hides their records from current class reports but does not delete educational records. Execute only after controller approval and verify active database, file storage and processor actions; record backup expiry separately. When the user account is deleted after all learning records have been handled, the application anonymises the retained request record by clearing its user link, description and internal notes while preserving minimal request type/status/timestamps.

## Tracking and response

The application records request type, description, identity-verification time, status, reviewer and internal notes. The requester sees only their own request and status; notes remain staff-only. Only administrators can access the queue.

The production owner must monitor the queue, acknowledge requests, calculate the applicable legal deadline from receipt, communicate any lawful extension/refusal and record the final response. Statutory deadlines, extension wording, fees and escalation path must be validated by counsel before this SOP is approved.

## Closure checklist

- [ ] Identity and authority verified.
- [ ] Scope and affected systems identified.
- [ ] Inventory and retention schedule consulted.
- [ ] Legal holds / statutory exceptions assessed.
- [ ] Processor and backup implications checked.
- [ ] Action approved and executed by an authorised person.
- [ ] Requester notified through verified channel.
- [ ] Outcome, exceptions, reviewer and dates recorded.
- [ ] Retained copies have a reason and review/expiry date.
