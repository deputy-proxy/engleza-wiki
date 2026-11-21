# Accessibility expectations for shared components

These requirements apply to new components and changes to existing learner-facing journeys. They are engineering defaults, not a substitute for content review or assistive-technology testing.

## Page shell and navigation

- Every page has one primary `main` landmark with a stable `id` and a visible-on-focus skip link.
- Header, primary, mobile, and legal navigation landmarks have meaningful accessible names when more than one navigation landmark exists.
- Navigation works with keyboard alone. Mobile menu controls are native buttons with `aria-controls` and an accurate `aria-expanded` value; hidden navigation is removed from the accessibility tree.
- Focus indicators are visible, high contrast, and not hidden beneath sticky headers or overlays.

## Links, buttons and touch targets

- Use anchors for navigation and buttons for actions. Do not make generic `div` or `span` elements clickable.
- Every control has a stable accessible name that describes its action or destination. Icon-only controls require an accessible name; decorative SVGs are hidden from assistive technology.
- State is exposed with native semantics or ARIA state such as `aria-pressed`, `aria-expanded`, `aria-current`, or `aria-selected` where appropriate.
- Interactive targets should be at least 24 by 24 CSS pixels or have sufficient separation under WCAG 2.2 SC 2.5.8. Prefer 44 by 44 CSS pixels for primary touch controls.

## Forms and validation

- Every input, select, and textarea has a programmatically associated visible label. Placeholder text is not a label.
- Use appropriate autocomplete tokens, input types, and native required constraints.
- Invalid fields expose `aria-invalid="true"`; connect explanatory errors with `aria-describedby` and announce submission errors through an alert or status region.
- Preserve entered values and focus predictably after validation and Livewire updates.

## Dynamic Livewire interactions

- Keep keyboard focus on the triggering control after a state update unless the action intentionally navigates or opens a dialog.
- Announce meaningful result, success, validation, and state changes through a concise `role="status"` or `role="alert"` region. Do not announce every keystroke or timer tick.
- Use labelled progressbars with valid `aria-valuemin`, `aria-valuemax`, and `aria-valuenow` values. Expose selected answers through native controls or ARIA state, not color alone.
- Avoid replacing a focused element with a different element during a morph; keep stable keys and IDs where repeated elements can change.

## Timers and progress

- Timers have a clear accessible name and do not place a per-second countdown in a live region.
- Announce actionable time warnings at sensible thresholds and announce expiry once.
- Progress includes both a visible indicator and a programmatic value/name. Current question or step navigation exposes the current item with `aria-current`.

## Media and motion

- Audio and video controls have descriptive accessible names. Meaningful video requires captions; meaningful audio/video requires a transcript or equivalent text alternative.
- Motion is not the only way to access information or functionality. Respect `prefers-reduced-motion` for decorative animation and pointer-driven effects.
- Do not autoplay audio or video with sound. Provide pause/stop controls for moving content where required.

## Color, contrast and visual presentation

- Normal text meets a 4.5:1 contrast ratio; large text meets 3:1. Non-text controls and meaningful graphical indicators meet 3:1 against adjacent colors.
- Never communicate correctness, selection, progress, or errors through color alone. Include text, shape, icon, or programmatic state.
- Verify focus visibility, 200%/400% zoom, reflow at 320 CSS pixels, and forced-colors/high-contrast mode.

## Required verification

- Run `yarn test:e2e` including the axe-core WCAG 2.2 A/AA checks and keyboard interaction tests.
- Run `composer ci:check`, `yarn test`, and `yarn build`.
- Complete the manual checklist in [Accessibility audit and remediation report](accessibility.md), including keyboard-only and representative NVDA/VoiceOver journeys. Do not report conformance based only on automated scans.
