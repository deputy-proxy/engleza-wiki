# Speaking and pronunciation practice MVP

## Learner experience

1. Open a published speaking resource or reach it from an enrolled learning path.
2. Read the prompt and the named target skill.
3. Follow the learner instructions and response expectation.
4. Optionally use the preparation and speaking timers.
5. Speak aloud without typing or submitting the spoken response.
6. Review the task's self-check criteria and reflection prompt.
7. When the activity is part of a learning path, return to the lesson and choose **I practised this aloud**. This records a self-attested completion, not a quality score.

Learners do not need an account to open and practise a public speaking resource. An account and active learning-path enrolment are required to record curriculum progress.

## Authoring requirements

Every speaking resource must contain:

- a clear, level-appropriate prompt;
- one explicit target skill (for example, giving opinions, narrative fluency, word stress or connected speech);
- preparation time from 0 to 300 seconds;
- speaking time from 15 to 600 seconds;
- practical learner instructions;
- an expected response that defines the communicative task;
- observable self-check criteria, one per line;
- a reflection prompt.

The parent resource must have the appropriate CEFR level, skills, topic taxonomy and editorial approval. Editing the structured speaking task invalidates the parent resource's editorial approval. The editorial reviewer must check task clarity, CEFR fit, skill alignment, natural language, accessibility and whether the self-check criteria are useful and non-misleading.

## Feedback and claims

The MVP offers guidance and self-reflection only. It does not measure phoneme accuracy, stress, intonation, fluency, grammar or vocabulary. Do not display a pronunciation percentage, pass/fail result, proficiency estimate or claim of automated assessment. Completion is self-attested.

## Browser and device support

The experience uses server-rendered HTML and the application's existing Alpine.js runtime. The prompt, instructions, response expectation, self-check and reflection remain readable without JavaScript. JavaScript adds only the optional countdown timer.

The implementation avoids `MediaRecorder`, `getUserMedia`, Web Speech API and external speech services. It therefore does not require microphone permission or browser-specific speech recognition support. Current evergreen desktop and mobile browsers supported by the main application are the compatibility target. The timer requires JavaScript; learners can still complete the task without it. A visible `<noscript>` message explains the fallback.

Automated verification covers the timer's countdown, phase switching, cancellation and reset using Node's test runner (`yarn test`). Laravel feature tests verify the rendered task, accessible timer status, non-recording claims and the non-JavaScript fallback markup. Browser verification was also run with Playwright against Chromium, Firefox and WebKit on desktop. The timer's preparation countdown, transition to speaking, reset, rendered task, and JavaScript-disabled fallback passed in all three engines. iPhone 13 emulation was tested with Chromium and WebKit, including touch operation of all timer controls and the speaking component's mobile width. Keyboard-only operation, visible focus and reduced-motion preference were checked in Chromium and Firefox.

These are browser-engine and device-emulation checks, not certification on physical devices. WebKit is not a substitute for native iOS Safari. Physical iOS Safari and Android Chrome remain unverified. The speaking component itself fits the 390px mobile viewport; the shared site header and cookie-preference shell have separate pre-existing horizontal overflow at this width and are outside this feature's scope.

### Browser verification checklist

- [x] Desktop Chromium, Firefox and WebKit: prompt, controls, countdown, phase transition and reset.
- [x] iPhone 13 emulation in Chromium and WebKit: responsive speaking component and touch controls.
- [x] Keyboard-only: all timer controls reachable and operable with visible focus (Chromium and Firefox).
- [x] Reduced-motion preference: no required animation or motion-dependent information (Chromium and Firefox).
- [x] JavaScript disabled: prompt, instructions, response expectation, criteria and reflection remain usable; timer fallback is visible.
- [ ] Physical iOS Safari and Android Chrome verification.

## Running automated checks

- `yarn test` runs the isolated speaking timer unit tests.
- `php artisan test --compact --filter=SpeakingPracticeTest` covers server-rendered behaviour, editorial invalidation and learning-path completion.
- The frontend CI job runs `yarn test` before building assets.

## Privacy and data handling

- No voice recording, audio upload, transcript, microphone permission or speech-provider request is used.
- No spoken response or free-text reflection is sent to the server or stored. The optional reflection note exists only in the current page until it is closed or refreshed.
- For a learning-path activity, the existing `lesson_activity_progress` row stores only activity ID, learner ID, status and completion timestamp.
- Public standalone practice does not persist an attempt or completion record.
- Existing account data export already includes `lesson_activity_progress`; no additional personal-data export table is introduced.
- There is no new audio retention or deletion workflow because the MVP does not create audio data.

## Teacher instructions

Teachers can assign an editorially approved speaking resource through the existing resource-based learning activity workflow. This MVP does not expose recordings or a teacher review queue. A learner's self-attested completion must not be interpreted as teacher-verified performance.
