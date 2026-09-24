# Edutech v1.0 — Slice 4.2 Implementation Record

## Status

Slice 4.2 implements the frontend-first setup wizard foundation. The wizard is exposed through the `[edutech_setup_wizard]` shortcode and is restricted to the normalized Designer identity. It is designed to resume after interruption and stores normalized setup configuration independently from legacy School Management Pro tables and identifiers.

## Delivered behavior

The wizard provides editable templates for Nigerian primary, Nigerian secondary, Nigerian tertiary, international primary, international secondary, university or college, and vocational institutions. Each template supplies starting values for country, institution type, education level, academic calendar, currency, and grading scheme; the designer may change these values before launch.

Wizard progress is persisted in the `edutech_setup_wizard` option after each save. The state includes school identity, branding URL, grading selection, selected modules, users, classes, subjects, and integrations so later slices can consume these configuration boundaries without duplicating setup logic. The wizard does not rename or overwrite legacy school records.

Launch checks produce actionable tasks for missing foundation values, school identity, grading configuration, and installation-health issues. The setup form uses a WordPress nonce, sanitized values, safe redirects, and a server-side Designer check. Non-designer operational users receive no setup controls and cannot save wizard state.

## Compatibility and migration boundary

This slice creates the configuration contract needed by the school/campus, academic structure, and module-navigation slices. It intentionally does not create legacy school rows, classes, subjects, or users: those writes belong to their respective domain slices and will consume the saved configuration through compatibility adapters. Existing WordPress and `WLSM_*` identifiers remain unchanged.

## Acceptance evidence

`tests/setup-wizard.php` verifies all supported template families, editable template defaults, resumable identity state, Designer authorization, actionable incomplete tasks, and preservation of prior state. The full smoke suite includes this test, first-party PHP syntax validation, and single-package build validation. The static security scan verifies direct-access guards, REST permission coverage, secret patterns, and dangerous execution patterns.

## Rollback

To roll back Slice 4.2, revert the Slice 4.2 commit and remove the `edutech_setup_wizard` option only if the saved draft configuration is no longer needed. No legacy tables or records are modified by this slice. Existing portal shortcodes, routes, and system-page assignments remain available after rollback.
