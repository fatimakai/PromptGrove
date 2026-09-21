# PromptForge

For the Render Free + Aiven public-demo deployment, see [the Phase 10 deployment guide](docs/DEPLOYMENT.md). The [case-study draft](docs/CASE_STUDY.md) is intentionally unpublished until live integrations are verified.

PromptForge is a collaborative library for creating, discovering, and refining prompts used with large language models. It is built with Laravel 12 and Livewire 3.

For a codebase-learning walkthrough, see the five end-to-end Mermaid diagrams and file-by-file responsibility maps in [`docs/ARCHITECTURE_FLOWS.md`](docs/ARCHITECTURE_FLOWS.md).

## Current features

- Public prompt discovery and private personal prompts
- Prompt text, target model, description, example input, and example output
- Tags, full prompt search, filters, and popularity/recent sorting
- Immutable version history with field-by-field comparison and safe restore
- Queued OpenRouter prompt analysis with intent, weaknesses, and an improved before/after prompt
- Google and GitHub OAuth sign-up/login with secure provider-account linking
- Authenticator-app two-factor authentication with one-time recovery codes
- Admin, Moderator, and User platform roles with permission-gated management screens
- Private shared collections with Owner, Editor, and Viewer membership roles
- Expiring invite links stored as hashes, plus prompt reporting and moderation
- Sandbox Pro subscriptions through Razorpay or PayPal with signed, idempotent webhooks
- OWASP-oriented hardening with shared prompt validation, AI burst limits, security headers, and collection audit logs
- Community upvotes and personal bookmarks
- Policy-based personal and shared prompt editing and deletion
- Individual and account-wide JSON exports
- Sanctum-authenticated Prompt API and public read-only API
- User profiles, email verification, and queued welcome email
- Responsive light/dark interface

The Phase 9 security review, deployment checklist, and residual-risk register are documented in [`SECURITY.md`](SECURITY.md).

## Stack

- PHP 8.2+ and Laravel 12
- Livewire 3, Blade, Tailwind CSS, Alpine.js, and Vite
- MySQL for production; SQLite in memory for tests
- Sanctum for API tokens
- Redis-compatible queues and cache
- Laravel Octane/Swoole-ready Docker setup

## Local setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
npm run build
php artisan serve
```

Configure database, mail, cache, and queue values in `.env`. Run a queue worker when using an asynchronous queue connection:

```bash
php artisan queue:work
```

To enable AI analysis, set `OPENROUTER_API_KEY` and optionally `OPENROUTER_MODEL`. `PROMPT_ANALYSIS_PER_HOUR` controls the Free per-user hourly cost limit, `PROMPT_ANALYSIS_PRO_PER_HOUR` controls Pro, and `PROMPT_ANALYSIS_BURST_PER_MINUTE` defaults to three for both plans.

PromptForge Pro costs $9/month USD in the demo and raises the AI quota from 5 to 25 analyses per hour while unlocking version history and shared collections. Billing is intentionally sandbox-only: configure `RAZORPAY_KEY_ID` with an `rzp_test_` key plus a Razorpay plan/webhook secret, and configure PayPal sandbox credentials, plan ID, and webhook ID. Point gateway webhooks to `/api/webhooks/razorpay` and `/api/webhooks/paypal`.

Create one recurring monthly plan for exactly USD 9.00 in each sandbox before adding its ID to `.env`. For Razorpay, subscribe the test webhook to the `subscription.authenticated`, `subscription.activated`, `subscription.charged`, `subscription.pending`, `subscription.halted`, `subscription.cancelled`, `subscription.completed`, and `subscription.expired` events. For PayPal, subscribe to the `BILLING.SUBSCRIPTION.ACTIVATED`, `UPDATED`, `SUSPENDED`, `CANCELLED`, and `EXPIRED` events. Checkout callbacks are verified and re-fetched from the provider; webhook signatures and event IDs are verified before local access changes.

To enable social login, create OAuth applications with Google and GitHub, then set their client IDs, client secrets, and callback URLs from `.env.example`. The local callbacks are `${APP_URL}/auth/google/callback` and `${APP_URL}/auth/github/callback`.

Two-factor authentication can be enabled from the profile page. Secrets and hashed recovery codes are encrypted at rest. The default login challenge expires after five minutes and allows five failed attempts per minute; both limits are configurable in `.env.example`.

`php artisan db:seed` creates portfolio accounts for `demo@promptforge.test`, `moderator@promptforge.test`, and `admin@promptforge.test`; the factory password is `password`. Platform roles use Spatie Laravel Permission. Collection invites expire after seven days and expose the raw token only once.

## Tests

```bash
composer test
npm run build
```

The test environment uses an in-memory SQLite database and does not require Docker or MySQL.

## Web routes

- `/` - landing page
- `/prompts` - public prompt discovery
- `/prompts/{slug}` - authorized prompt detail
- `/prompts/{slug}/history` - authorized personal/shared version timeline and comparison
- `/dashboard` - rankings
- `/prompts/mine` - personal library
- `/prompts/bookmarked` - bookmarks
- `/prompts/create` - prompt creation
- `/collections` - shared collection workspace
- `/collections/{slug}/audit` - owner-only collection security audit log
- `/billing` - Free/Pro comparison and sandbox checkout
- `/moderation` - Moderator/Admin report queue
- `/admin/users` - Admin-only platform role management

## API routes

Authentication uses bearer tokens returned by `POST /api/login`. Accounts with two-factor authentication enabled must also send `two_factor_code`; either a current six-digit TOTP or an unused recovery code is accepted.

- `GET /api/public/prompts`
- `GET /api/public/prompts/{slug}`
- `GET /api/prompts`
- `POST /api/prompts`
- `GET /api/prompts/{slug}`
- `PUT/PATCH /api/prompts/{slug}`
- `DELETE /api/prompts/{slug}`
- `GET /api/prompts/{slug}/analyses`
- `POST /api/prompts/{slug}/analyses`
- `GET /api/prompts/{slug}/analyses/{analysis}`
- `POST /api/logout`

Authenticated API endpoints always enforce prompt policies. Public endpoints only return prompts explicitly marked public.
