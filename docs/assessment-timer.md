# Assessment timer behavior

The assessment countdown is a client-side display and convenience trigger. The server remains authoritative: `TakeTest::mount()` checks the persisted attempt start time and duration, and `TestAttemptService::recordResponse()` rejects responses after expiry. `TestAttemptService::complete()` and `TestScoringService::score()` preserve the existing result when a completed or expired attempt is submitted again.

The Alpine `assessmentTimer` component updates the visible countdown once per second and announces only the five-minute, one-minute, and expiry thresholds. When the deadline is reached it marks submission as started before invoking the Livewire action, cancels its interval, and does not retry automatically. Alpine's `destroy()` lifecycle hook clears the interval when Livewire removes the component. The learner can still use the explicit submit control if a network request fails; the server validates the attempt state on every request.

Regression coverage lives in `tests/js/assessment-timer.test.js` for threshold announcements, one-shot submission, and interval cleanup. The Playwright assessment journey checks the rendered timer and threshold announcements. Server-side expiry and repeated completion are covered by `tests/Feature/AssessmentIntegrityTest.php`.
