# Cookie and consent behaviour

## Current implementation

- Essential Laravel session, CSRF and security cookies remain active.
- A first-visit banner offers reject, accept and customise actions for optional analytics.
- Preferences are stored in the encrypted, HttpOnly engleza_cookie_preferences cookie for 180 days, including policy version and update time. A cookie-policy version change invalidates the previous preference and displays the banner again.
- Preferences can be changed at /privacy/cookie-settings; the footer links to this page so withdrawal remains available.
- No analytics or advertising tag is installed by this repository. Accepting the optional analytics preference does not load a script or transmit analytics data.
- Future integrations must check the saved preference before loading scripts or setting non-essential cookies. Withdrawal must stop future collection and clear client identifiers where supported.
- Bunny Fonts is currently requested from fonts.bunny.net; it is a third-party resource request, not an analytics tag. Evaluate self-hosting before final policy approval.

## Release checks

- Inspect production GTM and all injected scripts, not only repository source.
- Test reject, accept, custom choice, reload persistence and withdrawal.
- Verify no non-essential cookies/network requests before opt-in.
- Verify no non-essential tags after withdrawal.
