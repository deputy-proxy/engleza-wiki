# Speaking and pronunciation MVP: decision record

- Status: accepted for the first MVP
- Scope: guided, self-directed spoken practice embedded in the existing resource and learning-path systems
- Decision date: 2026-11-19

## Decision

Deliver structured speaking tasks with a prompt, target skill, preparation time, speaking time, response expectation, learner instructions, self-check criteria and a reflection prompt. The learner speaks aloud independently. A lightweight browser timer supports preparation and response phases. In a learning path, the learner can mark the activity complete through an explicit self-attestation.

The MVP does not record or upload audio, request microphone access, transcribe speech, use browser speech recognition, send audio to an external service, provide teacher audio review, or calculate pronunciation/fluency scores.

## Options considered

| Option | Learning value | Privacy and consent | Browser/accessibility | Cost and reliability | Decision |
|---|---|---|---|---|---|
| Guided prompt + self-reflection | Repeatable communicative task and learner awareness; no objective speech diagnosis | No voice or transcript is collected | Ordinary HTML and existing Alpine.js; timer has a static fallback | No external service or ongoing cost | **Selected** |
| In-browser audio recording | Allows replay and self-comparison | Requires explicit consent, recording controls, private storage, access rules, retention and deletion | MediaRecorder support and permission UX vary; no-recording alternative needed | Storage, bandwidth and support costs | Deferred |
| Teacher-reviewed recordings | Enables human feedback | Adds learner voice data and teacher access/retention responsibilities | Upload and playback support required | Teacher time and storage | Deferred; audio governance is not part of this MVP |
| Browser speech recognition | Can produce a transcript for some utterances | Browser/provider processing and disclosure differ | API availability and language support are inconsistent; transcript is not a pronunciation assessment | No direct API fee in some browsers, but weak portability | Rejected for MVP |
| External speech/pronunciation service | Potentially richer feedback | Audio leaves the platform; processor, transfer, consent and retention review required | Vendor-specific browser/device support | Usage fees and vendor dependency | Deferred pending validated learning outcomes and privacy review |

## Rationale

The immediate gap is a practice experience, not a diagnostic engine. Guided tasks create a meaningful learner action without implying that the platform can judge pronunciation. This is the smallest useful implementation that avoids collecting sensitive voice data and remains independent of browser-specific speech APIs or paid providers.

## Consequences

- Completion means the learner attests that they practised; it is not evidence of speech quality.
- No audio, transcript, microphone permission, or learner reflection text is stored.
- Existing `lesson_activity_progress` records the activity completion timestamp and status for enrolled learners.
- The browser timer is a convenience only. The task remains usable without it.
- Any future recording, transcription, teacher review or automated assessment requires a separate product/privacy decision, explicit consent and documented access, retention and deletion controls before implementation.
