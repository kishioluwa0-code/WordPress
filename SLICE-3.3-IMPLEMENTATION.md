# Edutech v1.0 — Slice 3.3 Implementation Record

## Status

**Implemented and locally tested; production staging acceptance remains required before this slice is marked complete.**

## Delivered

Edutech now includes a centralized `Edutech_Policy` service for authorization decisions across frontend routes, API/resource operations, school and session scope, student ownership, and workflow abilities. The service exposes `can()`, `can_route()`, and compatibility wrappers (`edutech_can()`, `edutech_can_access()`, and `edutech_can_route()`).

The initial ability vocabulary separates dashboard and examination access from high-impact result workflows: `enter_scores`, `submit_scores`, `review_scores`, `approve_exam_results`, `finalize_results`, `publish_results`, `manage_settings`, `manage_cbt`, and `view_audit_log`.

Dashboard route access now delegates to the policy service. Existing role definitions, WLSM staff permission arrays, WordPress roles, and database records remain unchanged. Legacy permission entries continue to grant an ability, while safe role defaults support the new frontend modules during migration. The designer exception remains available for platform-level operations.

## Boundary checks

Resource authorization rejects a school outside the user’s assigned school IDs and rejects a mismatched academic session when a current session is present. Student resources are restricted to the student’s own record or the parent’s linked student IDs. These checks run before role defaults or legacy permissions are evaluated.

The service is intentionally stateless and accepts an explicit identity context. This makes it usable by frontend routes, REST permission callbacks, legacy adapters, downloads, exports, print views, and scheduled mutations without duplicating identity-resolution logic.

## Verification

- Central policy tests pass for role defaults, explicit permissions, designer access, school scope, session scope, student ownership, and parent-child ownership.
- Existing authentication, identity, feature-flag, portal-wrapper, JWT, smoke, syntax, security, and package tests remain in the verification path.
- Dashboard navigation and direct route checks use the same policy decision point.
- The policy service is included in the single-plugin package through the normal bootstrap and build process.

## Production acceptance still required

Create staging accounts for each portal role and verify that every migrated REST endpoint, legacy adapter, download, export, print view, and scheduled mutation calls `Edutech_Policy` with the correct resource scope. Confirm teacher assignment limits, examination workflow transitions, multiple schools and sessions, parent child switching, and audit events for denied or high-risk actions.

## Rollback

Deploy the previous commit and remove the policy bootstrap and dashboard delegation if necessary. The service introduces no schema or persisted-data changes, so rollback does not require data restoration.
