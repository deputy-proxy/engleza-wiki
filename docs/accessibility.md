# Accessibility audit and remediation report

## Scope and target

This audit addresses shared learner-facing journeys and targets WCAG 2.2 Level AA where applicable. It covers the shared page shell, navigation, homepage search, resource discovery, authentication forms, resource activities, flashcards, assessment controls, timers, progress indicators, and reduced-motion behavior.

This is an engineering remediation report, not a WCAG conformance certification. Automated browser checks cannot establish full conformance or replace assistive-technology testing.

## Baseline findings

The initial code review identified these issues:

- No skip link or stable focusable main-content target; primary navigation disappeared on narrow viewports without an equivalent menu.
- Primary and legal navigation landmarks were not consistently named.
- Login, registration, search, resource filters, and writing controls relied on nearby visual text without reliable programmatic associations.
- Login/registration validation messages were not consistently exposed as alerts or associated with invalid controls.
- Flashcards used a clickable `div` with simulated keyboard behavior instead of a native button.
- Assessment and flashcard progress indicators lacked progressbar semantics; assessment timer updates were announced every second.
- Listening media, breadcrumbs, and dynamic search suggestions lacked descriptive accessible names.
- The homepage contained duplicate pointer-driven 3D tilt scripts and did not honor the reduced-motion preference.
- Several blue text tokens did not meet the 4.5:1 contrast target on white backgrounds.

## Remediations

- Added a skip link, a focusable main landmark, named navigation landmarks, and a responsive keyboard-operable mobile menu with `aria-expanded` state.
- Added explicit form labels, autocomplete tokens, error associations, alert/status roles, and search landmark/suggestion semantics.
- Converted the flashcard interaction to a native button with an exposed pressed state and accessible progress/navigation labels.
- Added progressbar semantics and a timer that keeps per-second display updates out of the live region while announcing five-minute, one-minute, and expiry warnings.
- Added accessible names to listening media and breadcrumb landmarks.
- Disabled pointer-driven homepage tilt when `prefers-reduced-motion: reduce` is active and removed duplicate scripts.
- Improved shared focus-visible styling and adjusted text color tokens to meet the normal-text contrast target against white. Component-specific color combinations still require verification.

## Automated regression coverage

`tests/e2e/accessibility.spec.js` verifies:

- keyboard skip-link navigation to main content and keyboard operation of the mobile menu;
- axe-core WCAG 2.2 A/AA scans across public resources, writing validation, flashcards, assessment screens/results, learner dashboard, and learning paths, asserting zero serious or critical violations;
- authentication labels, autocomplete, and announced login/registration validation errors;
- writing validation errors associated with the response field;
- focus retention through flashcard and assessment Livewire updates;
- five-minute and one-minute assessment warnings, with the per-second timer excluded from live announcements;
- reduced-motion behavior on the homepage and accessible search/filter names.

The broader browser suite also covers learner login/dashboard, resource exercises, writing submissions and feedback, learning paths, and teacher/admin access. Run with `yarn test:e2e`.

## Manual QA checklist

The following checks remain required on representative production-like content and must be completed before claiming WCAG conformance:

- [ ] Keyboard-only pass through homepage, search suggestions, resource filters, flashcards, exercises, assessment, dashboard, and learning paths.
- [ ] NVDA with Firefox or Chrome: landmarks, headings, form errors, Livewire updates, flashcard state, progress, and timer warnings.
- [ ] VoiceOver with Safari: same representative learner journeys, including mobile navigation.
- [ ] 200% and 400% zoom / 320 CSS-pixel viewport; verify no loss of content or functionality.
- [ ] Windows forced-colors/high-contrast mode and visible focus indicators.
- [ ] Verify meaningful captions/transcripts for every published audio/video item and text alternatives for instructional media.
- [ ] Verify all state and feedback indicators remain understandable without color.
- [ ] Verify all interactive targets meet WCAG 2.2 target-size requirements or an applicable exception.

## Known limitations

- No manual NVDA, VoiceOver, forced-colors, or zoom session was performed as part of this code change.
- Media captions and transcripts depend on the actual published media assets and need content-level review.
- Automated checks cover representative journeys, not every route, dynamic content state, third-party component, or browser/assistive-technology combination.
- Contrast changes improve the shared blue text palette, but every color pairing and content-provided color must be checked during visual QA.
