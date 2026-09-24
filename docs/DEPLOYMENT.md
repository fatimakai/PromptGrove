# PromptGrove public demo deployment (Phase 10)

This is a sandbox-only portfolio deployment. It is not a production billing or email setup. Never share keys in chat or commit them to Git.

## 1. Prepare the services

1. Push the deployment files to a Git repository. Create a separate free Render Hobby workspace for PromptGrove.
2. In Aiven, create a free MySQL service. Copy the host, port, database (`defaultdb` unless you created another), username, password, and CA certificate from Aiven's console. Leave the database publicly reachable only as required by Aiven's free plan, and use TLS verification below.
3. In Render, create a Web Service from this repository: **Runtime: Docker; Dockerfile Path: `./Dockerfile`; Root Directory: repository root; Plan: Free; Health Check Path: `/up`**. Leave Docker Command empty so the image's entrypoint starts Apache. The container listens on port `10000` and runs migrations and the safe demo seeder on every start. Free services do not provide a pre-deploy migration step.
4. The first deployment can be configured with the final Render URL (`https://YOUR-SERVICE.onrender.com`) before the service becomes healthy. Render creates the URL in the service configuration. Record it for OAuth callbacks and billing webhooks.

## 2. Render environment variables

Enter these directly in Render's Environment tab. Generate a stable `APP_KEY` locally with `php artisan key:generate --show`; **do not regenerate it after accounts exist** or encrypted sessions and 2FA secrets become unreadable. Do not reuse your local development key.

| Variable | Value / purpose |
| --- | --- |
| `APP_NAME` | `PromptGrove` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | A unique `base64:...` key, kept secret and stable |
| `APP_URL` | Exact HTTPS Render service URL, without a trailing slash |
| `LOG_CHANNEL` | `stderr` (Render log stream) |
| `LOG_LEVEL` | `warning` (or `info` while diagnosing) |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Aiven MySQL values; `DB_PORT` is usually not 3306 |
| `AIVEN_CA_CERT_BASE64` | Base64 encoding of the downloaded Aiven CA PEM; the startup script writes it to `/tmp/aiven-ca.pem` and Laravel verifies the MySQL server certificate |
| `SESSION_DRIVER` | `database` |
| `SESSION_ENCRYPT` | `true` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `sync` (deliberate demo-only simplification) |
| `MAIL_MAILER` | `log` (the demo does not send email) |
| `DEMO_OAUTH_ONLY` | `true` (required by startup; server-side registration/reset block) |
| `STRIPE_ENABLED` | `true` (the hosted demo's only functional checkout provider) |
| `STRIPE_SECRET_KEY` | Stripe test secret key beginning with `sk_test_` |
| `STRIPE_PRO_MONTHLY_PRICE_ID` | Recurring USD 9/month Stripe test Price ID beginning with `price_` |
| `STRIPE_WEBHOOK_SECRET` | Signing secret beginning with `whsec_` for the deployed webhook endpoint |
| `RAZORPAY_ENABLED` | `false` (implemented and mock-tested, but not provider-validated because signup is unavailable from Pakistan) |
| `PAYPAL_ENABLED` | `false` (implemented and mock-tested, but not provider-validated because signup is unavailable from Pakistan) |
| `DEMO_ADMIN_EMAIL`, `DEMO_ADMIN_PASSWORD` | Optional private staff account. Use an email you own and a unique random password of at least 16 characters. Both values must be set together. |
| `DEMO_MODERATOR_EMAIL`, `DEMO_MODERATOR_PASSWORD` | Same for the private Moderator account; must use a different email. |

On Windows PowerShell, locally encode the downloaded CA file with `[Convert]::ToBase64String([IO.File]::ReadAllBytes('C:\path\to\ca.pem'))` and paste the result **only** into Render. Do not put the certificate, its base64 value, or database password in the repository. As an alternative to the base64 variable, mount a Render secret file and set `MYSQL_ATTR_SSL_CA` to its absolute path. Aiven's free MySQL connection is external to Render, so monitor free-tier outbound traffic and latency.

The startup script fails closed if the production app key, MySQL connection, verified TLS CA, or OAuth-only demo switch is missing. It runs `migrate --force`, idempotent `DemoDeploymentSeeder`, and config/view caching. Never run the general `DatabaseSeeder` publicly: it creates known-password development accounts. The deployment seeder creates four public prompts and a non-login library author; it will never seed a mock paid subscription. If staff credentials are set, it creates those accounts safely or rotates only an account already holding the same role. A conflicting existing email stops startup rather than promoting an unrelated user.

## 3. Configure deployed test-mode integrations

Use the exact `APP_URL` when registering callback URLs. Put each secret in Render Environment, not in source or chat.

| Service | Render variables | Provider callback / configuration |
| --- | --- | --- |
| Google OAuth | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | `https://YOUR-SERVICE.onrender.com/auth/google/callback`; set `GOOGLE_REDIRECT_URI` to the same exact URL. Configure the OAuth consent screen/test-user access as needed. |
| GitHub OAuth | `GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET` | `https://YOUR-SERVICE.onrender.com/auth/github/callback`; set `GITHUB_REDIRECT_URI` to the same exact URL. |
| OpenRouter | `OPENROUTER_API_KEY`, optionally `OPENROUTER_MODEL` | Fund/enable the account as required; test a bounded analysis request after deployment. |
| Stripe test mode | `STRIPE_ENABLED=true`, `STRIPE_SECRET_KEY`, `STRIPE_PRO_MONTHLY_PRICE_ID`, `STRIPE_WEBHOOK_SECRET` | Create a recurring USD 9/month test Price; webhook `https://YOUR-SERVICE.onrender.com/api/webhooks/stripe`. Subscribe to `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.paid`, and `invoice.payment_failed`. |
| PayPal sandbox (codebase only) | No credentials in Render; keep `PAYPAL_ENABLED=false` | OAuth, subscription creation/fetch/cancellation, approval-return verification, webhook signature verification, idempotent processing, and mocked feature tests remain implemented. It is not provider-validated because sandbox signup is unavailable from Pakistan. |
| Razorpay test (codebase only) | No credentials in Render; keep `RAZORPAY_ENABLED=false` | Subscription creation/fetch/cancellation, hosted Checkout, callback and webhook signatures, idempotent processing, and mocked feature tests remain implemented. It is not provider-validated because test-account signup is unavailable from Pakistan. |

Payment access is activated only after verified provider callbacks/webhooks. Do not use live payment credentials. On the free plan the service may sleep unless it receives traffic; incoming webhook delivery and external MySQL/OpenRouter calls should be exercised in sandbox before presenting the case study as fully operational.

The retained PayPal webhook adapter handles `BILLING.SUBSCRIPTION.ACTIVATED`, `UPDATED`, `SUSPENDED`, `CANCELLED`, and `EXPIRED`. The retained Razorpay webhook adapter handles `subscription.authenticated`, `activated`, `charged`, `pending`, `halted`, `cancelled`, `completed`, and `expired`. Their mocked tests remain runnable without provider accounts; neither adapter is described as provider-validated.

## 4. Validate the public demo

1. Confirm `/up`, `/`, `/prompts`, `/login`, and the built CSS/JS return successfully over HTTPS. Render terminates TLS; the app trusts Render's forwarded proxy headers and generates HTTPS URLs from `APP_URL`.
2. Confirm `GET` and `POST /register`, `/forgot-password`, and `/reset-password` return 404. The login page offers Google/GitHub and notes why email flows are unavailable. Existing private staff password login remains available; changing an account email through Profile is rejected server-side in demo mode.
3. Complete both OAuth flows with accounts you control, create a private prompt, visit the public seeded prompts, and test the Prompt API.
4. Run a low-volume OpenRouter analysis; check the resulting structured analysis and any cost/error logging. With `QUEUE_CONNECTION=sync`, the request blocks until analysis completes; a separate worker is intentionally not present.
5. Exercise Stripe test Checkout, the signed webhook, Pro entitlement, and cancellation using Stripe test data only. Razorpay and PayPal remain covered by mocked integration tests and must not be described as provider-validated.
6. Confirm staff login and permissions privately without publishing those credentials. A demo visitor must not be able to access `/admin/users` or `/moderation` without the relevant role.
7. Set up an external HTTP uptime check on `/up` if desired. One free service running continuously consumes about 720–744 of the workspace's 750 monthly free instance-hours; monitor usage, outbound bandwidth, and build minutes in Render. The free filesystem is ephemeral—do not store user files or the database there.

## Local verification and limits

Run `php artisan test` and `npm run build` before pushing. The Docker image can be tested locally with `docker build -t promptgrove-demo .` when Docker Desktop is running. The container will refuse production startup without complete environment settings and a reachable Aiven MySQL service. Local development via Sail remains separate and continues using the usual `.env` and `DatabaseSeeder`.

The public demo deliberately omits outbound email. Password registration, verification, reset, and welcome mail remain in the codebase and tests, but real delivery requires a verified sender domain and a configured mail transport. Collection sharing uses copyable invitation links. Render Free has no persistent disk, no separate free background worker, and no free pre-deploy command; the startup migration strategy assumes this single-instance demo, not a multi-instance production rollout.
