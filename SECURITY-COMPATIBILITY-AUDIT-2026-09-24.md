# Edutech v1.0 — Security and Compatibility Audit

**Audit date:** 24 September 2026  
**Build:** Edutech v1.0.0, Slice 3.5 working tree  
**Repository:** [kishioluwa0-code/WordPress](https://github.com/kishioluwa0-code/WordPress)

## Executive result

The source-level security controls, compatibility suite, PHP syntax validation, package build, ZIP integrity, checksum, REST permission assertions, direct-access guards, secret scan, and dangerous-execution scan all pass. The build is not dependency-clean: Composer reports 16 production dependency advisories across five packages, including one critical JMESPath issue and one high-severity Guzzle issue. The dependency findings are release blockers for an unrestricted production rollout and require a separately tested dependency-upgrade slice.

## Verification evidence

| Area | Result | Evidence |
|---|---|---|
| Focused Slice 3.5 tests | Pass | Context switching test covers allowed and denied school, campus, session, term, semester, child, and unknown dimensions. |
| Compatibility smoke suite | Pass | JWT, feature flags, portal wrapper, authentication, identity, policy, admin restriction, and context tests all pass. |
| PHP syntax | Pass | 561 first-party PHP files validated. |
| Static security scan | Pass | 540 guarded files; 282 REST routes; 281 centralized logged-in callbacks; one public callback; zero closures; no dangerous execution patterns. |
| Additional source assertions | Pass | Context REST registration, permission callback, ABSPATH guard, legacy metadata markers, secret scan, and dangerous-call scan verified. |
| Package integrity | Pass | `release/edutech-v1.0.0.zip` passes `unzip -t`; SHA-256 checksum verification passes. |
| Composer manifest/platform | Pass with warnings | Manifest is valid; platform requirements pass. Composer warns that the exact Kreait 5.26 constraint should be avoided. |
| Composer dependency audit | Fail / remediation required | 16 advisories affect five production packages. |

## Dependency findings

The lockfile currently contains `firebase/php-jwt` v6.11.1, `guzzlehttp/guzzle` 6.5.8, `kreait/firebase-php` 5.26.0, `stripe/stripe-php` v13.13.0, and `twilio/sdk` 5.42.2. Composer reports the following classes of vulnerabilities:

| Package | Severity | Finding summary | Current constraint | Remediation direction |
|---|---|---|---|---|
| `mtdowling/jmespath.php` | Critical | CompilerRuntime code injection through unescaped function names | Transitive through legacy HTTP stack | Upgrade the Guzzle/Kreait dependency graph and test all cloud integrations. |
| `guzzlehttp/guzzle` | High plus multiple medium | Host canonicalization, cookie scope, redirect referer, proxy header, and HTTPS proxy downgrade issues | `~6.0` / locked 6.5.8 | Move to a supported Guzzle 7/8 line after compatibility testing. |
| `guzzlehttp/psr7` | Multiple medium | Host confusion, CRLF injection, and authority parsing issues | Transitive | Upgrade with Guzzle and re-test all request construction and redirect behavior. |
| `firebase/php-jwt` | Low | Weak encryption behavior in versions below 7 | `^6.11` / locked 6.11.1 | Upgrade to v7 and verify JWT claim, algorithm, and key-format compatibility. |
| `symfony/polyfill-intl-idn` | Low | Insecure equivalence for selected punycode labels | Transitive | Upgrade the dependency graph and rerun URL/email compatibility tests. |

Available direct major upgrades are currently visible for JWT 7.2.0, Guzzle 8.2.0, Kreait Firebase 8.5.0, Stripe 21.3.2, and Twilio 8.12.1. These are not applied in Slice 3.5 because they cross major-version boundaries and could change payment, Firebase, SMS, JWT, and HTTP behavior. Create a dedicated dependency-remediation slice with integration fixtures, staging credentials, and rollback artifacts.

## Security boundary review

The context service validates every selected ID against server-derived allowed collections before persisting it. The context REST route requires authentication and the existing Edutech dashboard policy. Context changes invalidate the legacy user-info cache and Edutech identity cache and emit audit hooks. Legacy identifiers remain intact, and the browser receives only normalized IDs and labels rather than permissions or raw database rows.

The audit found no literal credentials, private keys, dangerous execution calls, or unguarded new PHP entry point. The existing REST scan continues to report one intentionally public callback and all other registered WLSM API routes use the centralized logged-in permission callback.

## Compatibility review

The build retains the `WLSM_*` tables and metadata keys, legacy school/session models, existing translation domain, portal assets, REST callback conventions, admin restriction exceptions, and scheduled/webhook compatibility paths. The context service is loaded through the plugin bootstrap and exposes filter-backed campus, term, and semester providers so it does not invent a database schema that the legacy build does not have.

Production acceptance remains outstanding. Staging must cover multiple schools, parent-linked children, revoked assignments, concurrent tabs, context changes across portal routes, exports and reports, scheduled jobs, webhooks, payments, REST reads, and audit listeners. Dependency remediation must be completed before a production security sign-off.

## Conclusion

**Application and compatibility controls: pass. Package integrity: pass. Dependency security: fail pending remediation.** The Slice 3.5 code can be reviewed and staged, but the current dependency lockfile should not be represented as fully production-secure until the critical and high advisories are resolved and the integrations are regression-tested.
