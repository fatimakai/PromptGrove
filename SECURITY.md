# PromptGrove security summary

This document records the Phase 9 OWASP hardening review. It describes controls present in the application, their boundaries, and the deployment settings that remain the operator's responsibility. It is not a claim of formal certification or a substitute for an independent penetration test.

## Security model

PromptGrove stores user-authored prompts, private collection membership, API tokens, OAuth identities, encrypted two-factor secrets, and sandbox subscription state. The primary threats considered are broken object-level authorization, cross-site scripting through prompt content, forged state-changing requests or billing callbacks, credential/session theft, AI-cost abuse, invite-token disclosure, and audit-log injection or flooding.

## Implemented controls

### Authentication and sessions

- Passwords use Laravel's configured password hasher. Successful login regenerates the session identifier, and logout invalidates the session and regenerates the CSRF token.
- Sanctum bearer tokens protect the private Prompt API. API policies apply to every individual prompt and analysis rather than relying only on route authentication.
- Optional TOTP two-factor authentication stores the secret and recovery-code array with Laravel encrypted casts; recovery codes are hashed and one-time use.
- OAuth callbacks validate provider state through Laravel Socialite. Provider accounts are linked by provider identifier, and account linking requires a verified provider email.
- Session cookies are HTTP-only and SameSite=Lax. The example environment enables encrypted session payloads. Production must additionally set `SESSION_SECURE_COOKIE=true` and serve only over HTTPS.

### Authorization and data isolation

- Prompt and collection policies enforce ownership and Owner/Editor/Viewer capabilities server-side for both browser and API access.
- Collection prompts are forced private at the model layer and can belong to at most one collection through the `prompts.collection_id` schema.
- Collection audit logs are readable only by the collection owner. The application exposes no update or delete endpoint for audit records.
- Admin and moderation screens require explicit Spatie permissions; route visibility in the UI is not treated as authorization.

### Input validation and output handling

- Create/update flows share server-side rules for prompt title, description, prompt body, target model, examples, visibility, and tags. All fields have type and length limits; visibility and roles use allowlists; tags allow at most 10 values of 50 characters each.
- Prompt text is intentionally free form because code and markup may be valid prompt content. The application rejects null bytes and unsupported control characters, prevents newlines in single-line fields, and canonicalizes CRLF/CR line endings to LF. It does not use destructive HTML stripping.
- Blade's escaped `{{ }}` output is used for user-controlled text. Eloquent/query-builder parameters are used for database access. JSON endpoints return structured JSON rather than constructing executable markup.
- The approach follows OWASP's guidance that free-form input validation, canonicalization, and length bounds complement—but do not replace—context-aware output encoding.

### AI cost and abuse controls

- AI analysis is authorized per prompt and snapshots the exact source text before queueing.
- A per-user hourly quota is enforced in the dispatcher for every UI and API analysis request: 5/hour on Free and 25/hour on Pro by default.
- A separate per-user burst limit defaults to 3/minute. The API analysis endpoint also has a named route throttle, providing an HTTP-layer limit before work is dispatched.
- Duplicate pending/processing analyses for the same prompt are rejected under a database row lock. Provider keys remain server-side and provider failures return generic messages.

### Collection security audit trail

- Security-relevant events record the timestamp, actor, collection, optional affected member/prompt, action, IP address, bounded user agent, and small allowlisted metadata.
- Recorded actions cover collection creation/update/delete and access, invite creation/view/revocation, member join/leave/removal/role change, prompt addition/removal, and audit-log viewing.
- Invite tokens, prompt bodies, session identifiers, API credentials, OAuth tokens, two-factor secrets, payment credentials, and raw webhook payloads are never stored in collection audit metadata.
- Repeated view events are coalesced per actor/action/collection for one hour to limit log-volume abuse. User-controlled strings are bounded and control characters are removed before log storage; Blade escapes them on display.
- Deleted users, prompts, or collections null their foreign-key reference while the chronological audit record and collection-name snapshot remain.

### CSRF, webhooks, and billing

- Browser routes use Laravel's `web` middleware group and CSRF token validation. Every state-changing Blade form includes `@csrf`; Livewire also uses Laravel's CSRF protection.
- Billing webhooks deliberately live in `routes/api.php`, outside cookie-authenticated browser CSRF. They use provider-specific cryptographic signature verification and idempotent provider event IDs instead. Invalid signatures are rejected before subscription state changes.
- Stripe is restricted to `sk_test_` credentials and is the deployed demo provider. Razorpay is restricted to `rzp_test_` credentials and PayPal to the sandbox host; both are disabled in the hosted demo because sandbox signup is unavailable from Pakistan. Checkout callbacks are identity checked, provider state is fetched server-to-server, and signed webhooks are deduplicated before Pro access changes.
- No card data is collected, processed, or stored by PromptGrove.

### Browser and transport headers

All responses receive `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `X-XSS-Protection: 0`, `Referrer-Policy: same-origin`, a restrictive Permissions Policy, `Cross-Origin-Opener-Policy: same-origin-allow-popups`, and a CSP baseline that locks `base-uri`, `form-action`, `frame-ancestors`, and `object-src` to safe values. Authenticated responses use private/no-store caching.

HSTS is emitted only for HTTPS requests in production, with a one-year duration and subdomains. This avoids poisoning local HTTP development while ensuring deployed browsers remain on HTTPS. A full script/style source CSP is deferred because Livewire/Alpine and optional provider checkout integrations require a tested nonce-based rollout; the current enforced baseline does not claim to mitigate every script-injection path.

### Secrets, errors, and dependencies

- Secrets and provider credentials are environment variables and `.env` is excluded from version control. `.env.example` contains names and safe defaults only.
- Production must use `APP_DEBUG=false`; validation and provider errors exposed to clients do not include credentials, raw provider responses, or stack traces.
- Queued jobs isolate provider latency from web requests. Request timeouts are bounded.
- CI/release checks should include `composer audit`, the PHPUnit suite, Blade compilation, and the Vite production build.

## Deployment checklist

1. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, HTTPS `APP_URL`, `SESSION_ENCRYPT=true`, and `SESSION_SECURE_COOKIE=true`.
2. Use production-grade database, cache, and queue services with least-privilege credentials; run a supervised queue worker.
3. Keep this portfolio build's payment providers in test/sandbox mode. Do not substitute live credentials without a separate tax, legal, privacy, and payment-compliance review.
4. Configure exact OAuth callback URLs and rotate any credential that has appeared in logs, chat, source control, or a client response.
5. Terminate TLS with a valid certificate, ensure every subdomain is HTTPS before retaining `includeSubDomains`, and preserve application security headers at the reverse proxy/CDN.
6. Restrict database access to audit logs, define a retention period, monitor 401/403/422/429 responses and webhook signature failures, and back up logs according to that policy.
7. Run `composer audit`, `composer test`, `php artisan view:cache`, and `npm run build` before release.

## Residual risks and follow-up work

- The enforced CSP is a safe baseline, not a complete source allowlist. A nonce-based CSP with deployed violation reporting should be tested across Livewire, Alpine, Vite, OAuth, and enabled payment-provider redirects before tightening `script-src`, `style-src`, `connect-src`, and `frame-src`.
- Database audit records are application-append-only but are not cryptographically tamper-evident. A real production system should ship them to restricted centralized storage and alert on logging failures.
- Rate limiting reduces automated abuse but is not bot detection. Distributed deployments must use a shared cache such as Redis.
- Collection access logs contain IP addresses and user agents. A production privacy policy must define lawful basis, access, retention, and deletion rules.
- Sandbox billing intentionally does not cover production PCI, tax, refund, dispute, or business-entity requirements.
- Dependencies and external provider behavior change over time; repeat this review and penetration testing before any production launch.

## Review references

- [OWASP Input Validation Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
- [OWASP HTTP Headers Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html)
- [OWASP Content Security Policy Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Content_Security_Policy_Cheat_Sheet.html)
- [Laravel 12 CSRF protection](https://laravel.com/docs/12.x/csrf)
- [Laravel 12 rate limiting](https://laravel.com/docs/12.x/rate-limiting)
