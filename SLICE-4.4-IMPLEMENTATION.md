# Edutech v1.0 — Slice 4.4 Implementation Record

## Status

Slice 4.4 introduces the Edutech frontend application shell and mockup-standard dashboard control center. The shell is role-aware, context-aware, theme-scoped, keyboard-accessible, and responsive across desktop, tablet, and mobile widths. Legacy account routes remain rendered inside the shell as compatibility adapters.

## Control-center structure

The shell contains a branded Edutech sidebar, institution and academic-session context card, role-aware navigation, mobile navigation drawer, notification affordance, user identity chip, sign-out action, welcome panel, KPI overview cards, quick-action workspace, notification panel, empty states, and a legacy-content region. Navigation continues to come from `Edutech_Dashboard_Routes` and is therefore subject to the existing policy service rather than being hard-coded into the visual layer.

The shell is rendered by `Edutech_Dashboard_Shell` around the existing authenticated account route. This keeps student, parent, teacher, staff, examination, and legacy workflows available while the individual domains migrate to native Edutech screens in later slices. It does not change legacy database identifiers, REST routes, or account records.

## Responsive and accessibility behavior

The dedicated `edutech-control-center.css` stylesheet uses a desktop two-column layout, tablet grid reduction, a mobile top bar and navigation disclosure, single-column KPI and action layouts, compact user controls, and a narrow-phone breakpoint for screens down to 320px. All controls have visible focus states, navigation uses semantic links and current-page state, the mobile menu uses a native disclosure element, and reduced-motion preferences are honored.

The existing design-system CSS remains the shared token and component foundation. The control-center stylesheet is separately enqueued after it, so the shell can evolve without globally overriding WordPress, Elementor, or Gutenberg styles.

## Acceptance evidence

`tests/dashboard-shell.php` verifies the shell contract, role metadata, institution context, navigation, mobile menu, KPI region, quick actions, and accessible structural hooks. The full smoke suite passes dependency compatibility, all existing identity and authorization tests, first-party syntax validation across 564 PHP files, and release-package integrity. The static security scan passes with all 543 first-party PHP files guarded.

## WordPress environment installation

The package is ready for installation as `release/edutech-v1.0.0.zip`. Automated SSH deployment is already defined for development, staging, and production, but the environment-specific `WP_DEPLOY_HOST`, `WP_DEPLOY_USER`, `WP_DEPLOY_PATH`, and `WP_DEPLOY_SSH_KEY` values must exist in the corresponding GitHub environments before a remote deployment can run. No remote installation is claimed until those credentials are available and a development deployment completes successfully.

## Rollback

Revert the Slice 4.4 commit to restore the prior legacy account presentation. The legacy route and data remain intact. The new stylesheet and shell class can be disabled independently by removing the shell bootstrap include/invocation and control-center enqueue if a staging rollback is required.
