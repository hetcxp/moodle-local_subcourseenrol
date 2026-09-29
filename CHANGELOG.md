# Changelog

All notable changes to this project will be documented in this file.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html)

## [2.1.3] — 2026-09-28
### Fixed
- `classes/observer.php`: distinguish active vs expired target enrolments via `is_enrolled($coursecontext, $userid, '', true)`, renewing expired enrolments and preserving suspended enrolments (DT-001).
- `classes/observer.php`: ensure master enrolment query checks that the master enrolment instance is enabled (`e.status = ENROL_INSTANCE_ENABLED`) and start window is valid (`ue.timestart < :timestart`) (DT-002).
- `classes/observer.php`: verify `enrol_is_enabled('manual')`, select enabled manual enrolment instances deterministically by `sortorder, id ASC`, and validate plugin object existence before enrolment (DT-003).
- `classes/observer.php`: validate `$targetcourseid <= 0` and instantiate context with `IGNORE_MISSING`, avoiding exceptions when referenced courses do not exist (DT-004).
### Tests
- `tests/observer_test.php`: added 7 regression tests covering expired target renewal, suspended target preservation, future master enrolment, disabled master instance, deterministic manual instance selection, disabled manual plugin, and non-existent target course (DT-005).
- `tests/observer_test.php`: resolved event redirection interception in `test_autoenrolled_event_is_fired` and fixed manual instance mock in `test_multiple_active_enrolments_prefers_perpetual`.
- `tests/behat/autoenrolment.feature`: modernized Gherkin step definitions to Moodle 5.x standards with `mod_subcourse` integration.

## [2.1.2] — 2026-09-21
### Added
- `LICENSE`: full GNU General Public License v3 text in plugin root for Moodle Plugins directory compliance.
- `lang/en`, `lang/es`: added `subcourseenrol:manage` capability definition string.
- `pix/`: added screenshots (`01_admin_settings.png`, `02_flow_and_audit_log.png`) for Moodle Plugins directory listing compliance.

## [2.1.1] — 2026-09-07
### Fixed
- `classes/privacy/provider.php`: remove space before colon in return type hint (`ReturnTypeHintSpacing` — Moodle CS)
- `db/events.php`: revert `internal` flag to `false`; `true` would mark the event as core-internal and prevent external observers from firing
- `settings.php`: wrap `howto_desc` in `format_text(..., FORMAT_HTML)` to ensure correct HTML rendering in admin heading
- `version.php`: add trailing comma to `dependencies` array and ensure inline comment ends with a period
- `classes/observer.php`: improve `@return` docblock for `get_master_enrolment()` to document the `timeend` field shape

## [2.1.0] — 2026-09-07
### Fixed
- Bug: multi-enrolment ORDER BY now prioritizes perpetual (timeend=0) over expiring enrolments (DT-S3)
### Changed
- observer.php: removed duplicate enrol_get_plugin() call (DT-S1)
- observer.php: added debugging() for objectid=0 edge case (DT-S2)
- privacy/provider.php: cleaned up informal PHPDoc comment (DT-S4)
- db/access.php: documented unused capability (DT-S5)
- lang/*/local_subcourseenrol.php: removed orphan string event_user_autoenrolled_desc (DT-S6)
### Tests
- observer_test.php: skip check moved to setUpBeforeClass() (DT-T1)
- observer_test.php: no-duplicate assertion added (DT-T2)
- observer_test.php: removed duplicate validate_data test (DT-T3)
- observer_test.php: added multi-enrolment perpetual regression test (DT-T4)
- event_test.php: added relateduserid assertion (DT-T5)
- autoenrolment.feature: added negative scenario coverage (DT-T6)

## [2.0.1] — 2026-09-04
### Fixed
- Resolved technical debt DT-1, DT-2, DT-3, DT-7
- Corrected mod_subcourse dependency version to 2025032001
- Set minimum Moodle version to 4.5 (2024100700)

## [2.0.0] — 2026-09-01
### Added
- Initial release: synchronous auto-enrolment via mod_subcourse event observer
- PHPUnit test suite (11 test cases)
- Behat acceptance test
- Privacy API null_provider
- Settings page with enable/disable toggle
- Event class user_autoenrolled with validate_data
