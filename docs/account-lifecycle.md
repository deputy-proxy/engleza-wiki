# Account lifecycle and authentication

## Account creation and email verification

- Registration is public and creates a `learner` account only. It signs the learner in, regenerates the session ID, and sends Laravel's built-in email verification notification.
- The learner is redirected to `/email/verify`. The signed verification URL is valid for 60 minutes by default (`AUTH_VERIFICATION_EXPIRE`) and is also rate-limited. The URL is bound to the authenticated account ID and the SHA-1 hash of its current email address; another account cannot use it.
- The verification page supports resending the message. Resends are limited to six requests per minute per client. An already-verified account is redirected to the dashboard without sending another notification.
- An unverified learner who logs in is redirected to the verification notice. Verification is not currently a blanket access gate: public resources, guest assessments and existing authenticated learning journeys remain available. Do not add a `verified` middleware requirement to those routes without an explicit product decision and migration plan for existing accounts.
- Changing an account's email address must clear `email_verified_at` and trigger a new verification notification. There is currently no public email-change workflow.

## Password recovery

- `/password/forgot` accepts an email address. The response is identical whether or not the address belongs to an account, so the endpoint does not disclose account existence.
- Laravel's password broker stores hashed, single-use reset tokens in `password_reset_tokens`. Tokens expire after 60 minutes (`AUTH_PASSWORD_RESET_EXPIRE`) and a new token cannot be requested for the same account more often than once per 60 seconds (`AUTH_PASSWORD_RESET_THROTTLE`). The request endpoint has an additional IP-based limit of five requests per minute.
- Reset completion requires the email, token, and a confirmed password of at least eight characters. Invalid, expired and already-used tokens are rejected with the same user-facing error.
- Successful reset changes the password hash and rotates the remember token. The user must log in again; password reset does not automatically authenticate the browser that opened the link.

## Session security

- Login and registration regenerate the session ID after authentication to mitigate session fixation.
- Logout calls the guard's logout operation, invalidates the current session and regenerates the CSRF token.
- Laravel's `auth.session` middleware is enabled for the web middleware group. It records the authenticated password hash in the session and logs out sessions whose stored hash no longer matches. This invalidates existing authenticated sessions on their next web request after a password reset/change. Remember-me cookies are also invalidated by rotating `remember_token` during reset.
- Login is limited to five attempts per minute per client. Password reset requests and submissions are separately rate-limited.
- Session cookie flags and trusted-proxy configuration are deployment concerns tracked in the production-hardening issue. Keep HTTPS enabled in production and configure the mail sender and transport before enabling account email flows.

## Routes

| Route | Purpose | Access |
|---|---|---|
| `GET /register`, `POST /register` | Create learner account | Guest |
| `GET /email/verify` | Verification instructions | Authenticated |
| `GET /email/verify/{id}/{hash}` | Consume signed verification link | Authenticated, signed, throttled |
| `POST /email/verification-notification` | Resend verification message | Authenticated, throttled |
| `GET /password/forgot`, `POST /password/email` | Request reset link | Guest, throttled |
| `GET /password/reset/{token}`, `POST /password/reset` | Set new password | Guest, throttled |
| `POST /login`, `POST /logout` | Authenticate / end session | Guest / authenticated |

## Mail configuration

Local and automated tests use `MAIL_MAILER=log`; inspect the configured application log for generated messages. Do not use the log mailer in production because users will not receive email.

For production, configure a supported delivery transport in the deployment environment, for example:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mail.example.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS=no-reply@engleza.wiki
MAIL_FROM_NAME="engleza.wiki"
```

Use the actual SMTP values supplied by the mail provider. Store credentials only in deployment secrets/environment variables, never in this repository. Verify SPF, DKIM and DMARC for the sender domain and send a real verification and password-reset message before production launch. If delivery is queued by a future mail configuration, confirm the queue processing mechanism separately; the current account notifications are sent synchronously.

## Configuration reference

| Variable | Default | Meaning |
|---|---:|---|
| `MAIL_MAILER` | `log` | Local/test default; use a real transport in production |
| `MAIL_FROM_ADDRESS` | `hello@example.com` | Sender address |
| `MAIL_FROM_NAME` | application name | Sender display name |
| `AUTH_VERIFICATION_EXPIRE` | `60` | Signed email verification link lifetime in minutes |
| `AUTH_PASSWORD_RESET_EXPIRE` | `60` | Password reset token lifetime in minutes |
| `AUTH_PASSWORD_RESET_THROTTLE` | `60` | Minimum seconds between reset token generations |

## Testing

Run the focused lifecycle suite with:

```bash
php artisan test --compact tests/Feature/AccountLifecycleTest.php
```

The suite covers registration/login session rotation, verification ownership and expiry, resend behaviour, non-enumerating reset requests, single-use/expired reset tokens, logout invalidation and preservation of guest learning access. CI additionally runs the complete Laravel test suite and static analysis.
