# Edutech v1.0 — Slice 3.4 Implementation Record

## Status

**Implemented and locally tested; production staging acceptance remains required before this slice is marked complete.**

## Delivered

Edutech now includes `Edutech_Admin_Restriction`, a bootstrap-level boundary that protects WordPress admin from ordinary operational users. Authenticated students, parents, teachers, staff, school administrators, and examination officers are redirected to the frontend dashboard when they request ordinary admin pages. Direct `admin-post.php` and unauthorized AJAX requests receive a 403 response rather than being allowed to execute legacy handlers.

The designer remains the platform-maintenance exception. Anonymous users are not intercepted by this service, allowing WordPress login and password-recovery flows to operate normally.

## Safe exceptions

The restriction does not block cron, REST requests, explicitly marked webhook requests, logout and password-recovery actions, or required compatibility actions for heartbeat, media attachment upload/query, attachment retrieval, and health checks. The safe action lists are filterable through `edutech_safe_admin_actions` and `edutech_safe_admin_post_actions` so integrations can add narrowly scoped exceptions without disabling the boundary globally.

Emergency recovery can be enabled explicitly with `EDUTECH_EMERGENCY_RECOVERY` or the `edutech_allow_emergency_recovery` filter. Every blocked request and every emergency-recovery pass emits `edutech_admin_restriction_audit` with the event type, user ID, canonical role, and sanitized request path. This provides a stable integration point for the platform audit-log service without introducing a schema migration in this slice.

## Compatibility guarantees

No legacy admin pages, AJAX handlers, scheduled hooks, webhook routes, database records, roles, or capabilities were removed. The restriction is applied at the request boundary and leaves existing handlers available to the designer and explicitly approved system exceptions during migration.

## Verification

- Admin restriction tests pass for operational users, designers, anonymous users, safe actions, cron, REST, webhooks, and emergency-recovery defaults.
- Existing compatibility tests remain in the smoke-test path.
- PHP syntax validation passes for all first-party files.
- Static security scan passes with updated direct-access guard baseline.
- Single-plugin ZIP build and integrity checks pass.

## Production acceptance still required

In staging, verify direct `wp-admin`, `admin-post.php`, and unauthorized `admin-ajax.php` requests for every operational role. Confirm that logout, password reset, media flows, cron, REST, payment/webhook callbacks, and emergency recovery continue to work. Register an audit-log listener and verify both blocked and emergency events. Confirm the designer can still use all required WordPress admin screens.

## Rollback

Deploy the previous commit and remove the admin-restriction bootstrap load and boot call. No persisted data is changed, so rollback does not require database restoration.
