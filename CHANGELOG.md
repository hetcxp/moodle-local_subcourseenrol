# Changelog

All notable changes to this project will be documented in this file.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html)

## [Unreleased]
### Added
- `LICENSE`: full GNU General Public License v3 text in plugin root for Moodle Plugins directory compliance

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
