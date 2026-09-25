# PromptGrove case study

- **Live demo:** [promptgrove-2hfa.onrender.com](https://promptgrove-2hfa.onrender.com)
- **Source code:** [github.com/fatimakai/PromptGrove](https://github.com/fatimakai/PromptGrove)
- **Stack:** Laravel 12, Livewire 3, MySQL, Sanctum, Tailwind CSS, Alpine.js, Docker, Aiven, and Render

## Overview

PromptGrove is a full-stack prompt library for individuals and lightweight teams. It turns prompts from disposable chat text into working documents that can be discovered, saved, versioned, improved, and shared.

The application combines public prompt discovery with private workspaces, immutable version history, AI-assisted analysis through OpenRouter, shared collections with Owner/Editor/Viewer permissions, Google and GitHub OAuth, custom TOTP two-factor authentication, a Sanctum-authenticated API, and provider-isolated test-mode subscription billing.

## The problem

Useful prompts are often scattered across chat histories, notes, and team messages. That makes it difficult to find a proven prompt, understand how it changed, or safely share it without exposing unrelated private work.

PromptGrove addresses that problem with one searchable system for prompt discovery, iteration, ownership, and collaboration. Public prompts remain discoverable, personal prompts remain private, and team prompts inherit access from a single shared collection.

## Product capabilities

- Public discovery with search, tags, model filters, popularity sorting, upvotes, and bookmarks
- Private prompt creation with example inputs and outputs
- Immutable version history, field-by-field comparison, and one-click restoration
- Structured AI analysis that records intent, weaknesses, and an improved prompt without overwriting the source
- Shared collections with Owner, Editor, and Viewer roles, expiring invite links, and audit logs
- Google and GitHub OAuth with secure account linking
- Authenticator-app two-factor authentication with replay protection and hashed recovery codes
- Sanctum-authenticated prompt APIs and a public read-only API
- JSON export for individual prompts and complete personal libraries
- Test-mode Pro subscriptions with Stripe as the deployed provider and retained Razorpay and PayPal adapters

## Architecture and implementation

Laravel policies enforce resource-level authorization while route middleware handles authentication, email verification, platform permissions, Pro entitlements, throttling, and demo restrictions. Livewire provides the reactive browser experience, and shared services keep validation, prompt versioning, AI dispatch, billing reconciliation, and collection auditing consistent across web and API entry points.

External responses are treated as untrusted input. OAuth identities are verified before linking, AI output must pass a structured schema, billing callbacks are matched to the signed-in user, and subscription webhooks are signature-checked and deduplicated before local access changes.

The repository includes five end-to-end architecture flows with every component mapped to its actual file path in [`ARCHITECTURE_FLOWS.md`](ARCHITECTURE_FLOWS.md).

## Security and reliability

The security pass focused on broken object-level authorization, stored XSS, forged callbacks, replay attacks, AI-cost abuse, invite-token disclosure, and cross-user subscription binding. Important safeguards include:

- Policy checks on personal and collection-owned prompts
- Hashed, expiring, revocable collection invite tokens
- Encrypted TOTP secrets and single-use recovery codes
- TOTP replay prevention and rate-limited challenges
- Signed and idempotent billing webhooks
- Server-to-server subscription verification before granting Pro access
- Strict test-mode credential guards that reject live payment keys
- Security headers, encrypted secure cookies, bounded validation, and AI burst limits
- Database uniqueness constraints for external identities, versions, subscriptions, and webhook events

The automated suite currently passes **135 tests with 626 assertions**. It covers authentication, OAuth account linking, two-factor challenges, prompt authorization, version restoration, collaboration roles, API access, AI analysis orchestration, billing callbacks, webhook replay protection, and deployment safeguards.

## Deployment

The public demo runs in Docker on Render with Aiven MySQL over a verified CA connection. Startup fails closed when the production key, MySQL configuration, OAuth-only demo switch, or database CA certificate is missing. Each deployment runs migrations, an idempotent public-demo seeder, and Laravel configuration/view caching before Apache starts.

The free portfolio deployment deliberately uses synchronous jobs because it has no separate background worker. Production-scale deployment would move AI analysis and email delivery to dedicated queue workers. Outbound email is not presented as a live feature because the demo does not have a verified sending domain.

Billing remains test-mode only. Stripe is the deployed demo provider. Razorpay and PayPal are fully implemented and covered by mocked feature tests, but they are not described as provider-validated because sandbox-account signup is unavailable from Pakistan. No real payments are accepted.

## Deliberate scope decision

A prompt can belong to one ownership context: personal, public, or one shared collection. Allowing the same prompt to inherit permissions from multiple collections would require conflict resolution across roles, visibility, removal, and audit history. The single-context rule keeps authorization explainable and makes access revocation predictable while still supporting the collaboration goals of the project.

## Current verification status

The deployed application, HTTPS configuration, MySQL TLS connection, database-backed sessions, public pages, public API, OAuth-only route restrictions, security headers, and production asset build have been verified. Live end-to-end checks for Google/GitHub OAuth, OpenRouter analysis, and Stripe test Checkout are being completed separately; the portfolio description should not present those deployed integrations as verified until those checks pass.
