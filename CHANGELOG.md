# Changelog

All notable changes to this project will be documented in this file.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)
Versioning: [Semantic Versioning](https://semver.org/spec/v2.0.0.html)

## [2.1.6] — 2026-11-19
### Security & Fixed
- `classes/observer.php`: enforced cross-method **Suspension Safety** (`REL-1`) checking `{user_enrolments}` joined with `{enrol}` across all enrolment methods in the target course so any suspended enrolment blocks automatic re-enrolment.
- `classes/observer.php`: added global `try/catch (\Throwable)` resilience wrapper (`REL-2`) with `debugging(..., DEBUG_DEVELOPER)` logging to prevent observer failures from breaking course module navigation.
- `classes/observer.php`: skipped auto-enrolment during "Login as" (`\core\session\manager::is_loggedinas()`) sessions and filtered deleted/suspended users (`u.deleted = 0 AND u.suspended = 0`) (`REL-4`).
- `classes/observer.php`: filtered `SITEID` and self-referential target courses (`$targetcourseid === $mastercourseid`) (`REL-5`), and ensured unique first column `ue.id` in master enrolment query (`REL-6`).
- `classes/event/user_autoenrolled.php`: implemented `get_objectid_mapping()` and `get_other_mapping()` (`REL-7`) for Moodle backup/restore compatibility, plus `#[\Override]` attributes (`REL-11`).
- `classes/privacy/provider.php`: added PHP 8.3 `#[\Override]` attribute (`REL-11`).
- `classes/local/role_resolver.php`: validated `CONTEXT_COURSE` assignability via `role_context_levels` with `debugging()` notice on invalid/null resolution (`REL-10`).
- `settings.php` & `lang/*/local_subcourseenrol.php`: passed `['context' => \context_system::instance()]` to `format_text()`, normalized `howto_desc` to a single AMOS-compliant line reflecting the configurable enrolment role (`REL-9`), and sorted language strings alphabetically.

### Removed
- `db/access.php` & `lang/*/local_subcourseenrol.php`: removed unused `local/subcourseenrol:manage` capability (`REL-8`).

## [2.1.5] — 2026-10-16
### Added
- `classes/local/role_resolver.php`: decoupled role resolution into isolated domain helper (`resolve_target_role()`, `reset_cache()`) adhering to CCP bounded contexts.
- `version.php`: declared `$plugin->supported = [405, 503];` certifying official support from Moodle 4.5 LTS to Moodle 5.3+.
- `classes/observer.php`: concurrency locking via `\core\lock\lock_config::get_lock_factory('local_subcourseenrol')` preventing TOCTOU race conditions on simultaneous subcourse activity clicks.
- `settings.php`: configurable enrolment role (`local_subcourseenrol/roleid`) with course-context assignable role selector and fallback to student archetype.
- `lang/en`, `lang/es`: added localized strings for `roleid`, `roleid_desc`, and `defaultrole_archetype`.
- `db/upgrade.php`: added upgrade savepoint for version `2026101600`.
- `tests/observer_test.php`: added tests for repeated event idempotency, event execution for distinct users, configured role application, fallback on invalid role, and lock failure contention handling.
- `tests/fixtures/test_failing_lock_factory.php`: test fixture simulating lock contention.

### Fixed
- `classes/event/user_autoenrolled.php`: updated `get_url()` from deprecated `/enrol/users.php` to canonical `/user/index.php`.
- `classes/observer.php`: timestart inheritance from master course enrolment (`ue.timestart`) preserved in target course enrolment.
- `classes/observer.php`: deterministic multi-enrolment query ordering by `timeend`, `timestart`, and `id` with explicit `LIMIT 1`.
- `classes/observer.php`: per-request static memoization of `enabled` setting, target role, and manual enrolment instances with `reset_caches()`.
- `classes/observer.php`: modular decomposition of `subcourse_viewed()` into focused private helper methods reducing cyclomatic complexity below 8.
- `tests/observer_test.php`: replaced invalid `markTestSuiteSkipped` with `markTestSkipped` in `setUpBeforeClass()` for clean skips when `mod_subcourse` is absent.
- `tests/observer_test.php`: hardened `assertDebuggingCalled` with explicit expected error messages.
- `tests/event_test.php`: synchronized `get_url` assertion with `/user/index.php`.
- `phpunit.xml`: cleaned non-existent boilerplate `<file>` entries from `<source><include>`.

## [2.1.4] — 2026-10-13
### Added
- `classes/observer.php`: defensive checks for empty and guest users (`isguestuser($userid) || $userid <= 0`) avoiding invalid auto-enrolment attempts and logging developer notice for non-positive user IDs.
- `classes/observer.php`: wrapped auto-enrolment and event triggering in separated, defensive `try / catch (\Throwable $e)` blocks with developer stack traces to reduce the likelihood of enrolment exceptions interrupting course module navigation.
- `db/upgrade.php`: added upgrade savepoint for version `2026101300`.
- `tests/observer_test.php`: added test cases `test_guest_user_no_enrolment`, `test_invalid_userid_no_enrolment`, and `test_timestart_exact_boundary_enrolment`.

### Fixed
- `classes/observer.php`: fixed strict start time boundary from `< :timestart` to `<= :timestart` in `get_master_enrolment()`.
- CodeChecker / Moodle CS: removed redundant `MOODLE_INTERNAL` checks from autoloaded classes and test classes (`classes/observer.php`, `classes/event/user_autoenrolled.php`, `classes/privacy/provider.php`, `db/upgrade.php`, `tests/event_test.php`, `tests/observer_test.php`).
- `db/upgrade.php`: fixed comment capitalization warning.

### Tests
- PHPUnit 11 & Moodle 5.3+: migrated test metadata from deprecated phpdoc `@covers` docblocks to PHP 8 Attributes (`#[\PHPUnit\Framework\Attributes\CoversClass]`) across all test classes.
- `tests/observer_test.php`: added explicit `assertDebuggingCalled` assertions for negative manual enrolment test scenarios, eliminating all PHPUnit notices.

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
