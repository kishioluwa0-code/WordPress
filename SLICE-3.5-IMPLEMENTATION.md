# Edutech v1.0 — Slice 3.5 Implementation Record

## Status

**Implemented and locally verified; production staging acceptance and dependency remediation remain required.**

## Delivered

Edutech now provides a server-side context service for school, campus, academic-session, term, semester, and parent-child selections. `Edutech_Context` derives available school and child IDs from the canonical identity service, validates active schools through the legacy school model, resolves sessions through the legacy session model, and exposes filter-backed providers for campus, term, and semester data that is not yet represented by the legacy schema.

Selected values are persisted in user metadata and validated against the account’s currently assigned collections before a write occurs. School changes also update the preserved `wlsm_school_id` metadata used by legacy handlers; session and parent-child selections preserve `wlsm_current_session` and `wlsm_current_student_id`. An unauthorised ID therefore cannot become active merely by changing a browser parameter.

The service registers authenticated `GET` and `POST` REST operations at `/wp-json/edutech/v1/context`. A POST accepts one dimension and one integer value, validates it server-side, updates only the matching metadata key, invalidates the legacy user-info and Edutech identity caches, and emits `edutech_context_changed`. The frontend portal receives the validated context and available targets through `edutechContext` localization. No sensitive permissions or database records are exposed to the browser.

## Compatibility guarantees

Existing WordPress user metadata, WLSM identifiers, legacy school/session models, policy checks, REST permission conventions, and portal assets remain in place. The service is an adapter and does not rename or delete legacy tables, routes, actions, shortcodes, or roles. Campus, term, and semester providers are intentionally filterable so future schema-aware modules can supply scoped values without weakening the boundary.

## Verification

The focused context test passes for allowed and denied school, campus, session, term, semester, parent-child, and unknown dimensions. The complete smoke suite passes all compatibility tests, validates **561 first-party PHP files**, and builds the installable package. The static security scan passes with 540 guarded first-party files, 282 REST routes with 281 centralized logged-in callbacks and one public callback, no dangerous execution patterns, and no detected credential literals. Package ZIP integrity and SHA-256 verification pass.

The dependency audit is not clean: the current lockfile contains 16 advisories across five packages, including one critical and one high severity finding. Details and remediation guidance are recorded in `SECURITY-COMPATIBILITY-AUDIT-2026-09-24.md`.

## Production acceptance still required

In staging, exercise multi-school staff switching, parent child switching, invalid and revoked IDs, session changes, and every available campus/term/semester provider. Verify that changing any ID in the browser cannot expose another school, session, campus, term, semester, or child. Register listeners for `edutech_context_changed` and `edutech_context_cache_invalidated`, confirm audit events, and test concurrent tabs after a context change. Confirm all legacy reports, exports, payments, scheduled jobs, webhooks, REST reads, and portal pages use the validated context.

## Rollback

Deploy the previous commit and remove the context bootstrap load, boot call, REST service, and frontend localization. No database tables are changed. Existing legacy metadata remains intact, so rollback requires no data restoration; newly written Edutech context metadata can be ignored or removed separately if desired.
