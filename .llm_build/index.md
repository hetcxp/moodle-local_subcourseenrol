# Codebase Contract Index

## `classes/event/user_autoenrolled.php`
- class user_autoenrolled [L36]
  * protected init() [L41]
  * public get_name() [L52]
  * public get_description() [L61]
  * public get_url() [L72]
  * protected validate_data() [L82]

## `classes/observer.php`
- class observer [L36]
  * private is_enabled(): bool [L43]
  * private get_master_enrolment(int $courseid, int $userid): ?\stdClass [L57]
  * private resolve_enrol_instance(int $courseid): ?\stdClass [L87]
  * private resolve_student_role(): ?\stdClass [L110]
  * public subcourse_viewed(\mod_subcourse\event\course_module_viewed $event): void [L131]

## `classes/privacy/provider.php`
- class provider [L36]
  * public get_reason(): string [L43]

## `db/access.php`

## `db/events.php`

## `db/upgrade.php`

## `lang/en/local_subcourseenrol.php`

## `lang/es/local_subcourseenrol.php`

## `settings.php`

## `tests/event_test.php`
- class event_test [L39]
  * protected setUp(): void [L44]
  * public test_event_properties(): void [L52]
  * public test_get_name(): void [L81]
  * public test_get_description(): void [L89]
  * public test_get_url(): void [L114]
  * public test_validate_data_missing_mastercourseid(): void [L138]

## `tests/observer_test.php`
- class observer_test [L39]
  * public setUpBeforeClass(): void [L44]
  * protected setUp(): void [L55]
  * private setup_scenario(): array [L65]
  * private trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void [L90]
  * public test_auto_enrolment_workflow(): void [L131]
  * public test_plugin_disabled_no_enrolment(): void [L146]
  * public test_user_already_enrolled_no_duplicate(): void [L160]
  * public test_manual_instance_missing_no_enrolment(): void [L180]
  * public test_manual_instance_disabled_no_enrolment(): void [L197]
  * public test_timeend_inherited_from_master(): void [L213]
  * public test_subcourse_without_refcourse_no_enrolment(): void [L235]
  * public test_student_role_fallback_via_archetype(): void [L248]
  * public test_autoenrolled_event_is_fired(): void [L266]
  * public test_master_enrolment_timeend_zero_no_expiry(): void [L292]
  * public test_multiple_active_enrolments_prefers_perpetual(): void [L313]

## `version.php`
