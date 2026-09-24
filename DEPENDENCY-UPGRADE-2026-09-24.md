# Secure Dependency Upgrade — 24 September 2026

## Scope

This branch upgrades the production Composer graph in an isolated worktree and preserves the existing WordPress plugin integration surface. The previous lockfile contained 16 advisories, including critical JMESPath and high-severity Guzzle findings.

## Direct dependency result

| Package | Previous | Upgraded |
|---|---:|---:|
| `firebase/php-jwt` | v6.11.1 | v7.2.0 |
| `guzzlehttp/guzzle` | 6.5.8 | 7.15.5 |
| `kreait/firebase-php` | 5.26.0 | 8.5.0 |
| `stripe/stripe-php` | v13.13.0 | v21.3.2 |
| `twilio/sdk` | 5.42.2 | 8.12.1 |

The lockfile also upgrades the related PSR, Google Cloud, JMESPath, JWT, HTTP, and Symfony packages. Composer’s dependency resolver completed successfully and reported **No security vulnerability advisories found** during installation.

At the final verification attempt, `composer audit --locked --no-dev` could not contact Packagist’s advisory endpoint because the request timed out after 10 seconds. This is an external network retrieval failure, not a reported vulnerability. A connected CI or staging runner must repeat the live audit before production release.

## Compatibility fixes

Kreait 8 removed `CloudMessage::withTarget()`. The Firebase notification path now constructs messages with `CloudMessage::new()->toToken($token)->withNotification(...)`, while retaining the existing service-account file contract, error handling, and notification payload.

JWT 7 enforces the minimum HS256 key length. The existing compatibility fixture now generates a 32-byte test-only key; no production secret was introduced.

## Fresh Firebase environments

The connected Firebase account now has three isolated projects with optional Gemini and Google Analytics disabled:

| Environment | Firebase project name | Project ID |
|---|---|---|
| Development | Edutech Dev | `edutech-dev-95323` |
| Staging | Edutech Staging | `edutech-staging-150ec` |
| Production | Edutech Production | `edutech-staging-20d90` |

No service-account keys were downloaded, committed, or placed in the repository. Credential provisioning remains a controlled staging step and must use protected server-side secrets.

## Verification evidence

The deterministic dependency compatibility test passes for Kreait Firebase 8.5, Guzzle 7.15, JWT 7.2, Stripe 21.3, and Twilio 8.12. It verifies Firebase token and notification serialization, Guzzle request/response handling, JWT encode/decode, and class availability for Stripe and Twilio.

The full smoke suite passes Composer validation, platform checks, all existing plugin compatibility tests, the dependency compatibility test, 561 first-party PHP syntax checks, package generation, ZIP integrity, manifest assertions, and checksum generation. The static security scan passes with 540 guarded files, 282 REST routes, 281 centralized callbacks, one intentional public callback, no credential literals, and no dangerous execution patterns.

## Release gate

This branch is suitable for code review and staging deployment. Before production enablement, run the live Composer audit from CI, provision one protected service-account secret per Firebase environment, register the matching project IDs in deployment configuration, test real FCM delivery and failure handling in dev and staging, then promote the exact verified lockfile and package to production. Rollback is the previous `composer.lock`, vendor package, and plugin commit.
