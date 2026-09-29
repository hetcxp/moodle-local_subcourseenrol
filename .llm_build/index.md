# Codebase Contract Index

## `classes/event/user_autoenrolled.php`
- class user_autoenrolled [L35]
  * protected init() [L39]
  * public get_name() [L50]
  * public get_description() [L59]
  * public get_url() [L70]
  * protected validate_data() [L80]

## `classes/observer.php`
- class observer [L35]
  * private is_enabled(): bool [L41]
  * private get_master_enrolment(int $courseid, int $userid): ?\stdClass [L55]
  * private resolve_enrol_instance(int $courseid): ?\stdClass [L89]
  * private resolve_student_role(): ?\stdClass [L119]
  * public subcourse_viewed(\mod_subcourse\event\course_module_viewed $event): void [L140]

## `classes/privacy/provider.php`
- class provider [L35]
  * public get_reason(): string [L41]

## `db/access.php`

## `db/events.php`

## `db/upgrade.php`

## `lang/en/local_subcourseenrol.php`

## `lang/es/local_subcourseenrol.php`

## `settings.php`

## `tests/event_test.php`
- class event_test [L38]
  * protected setUp(): void [L42]
  * public test_event_properties(): void [L50]
  * public test_get_name(): void [L79]
  * public test_get_description(): void [L87]
  * public test_get_url(): void [L112]
  * public test_validate_data_missing_mastercourseid(): void [L136]

## `tests/observer_test.php`
- class observer_test [L38]
  * public setUpBeforeClass(): void [L42]
  * protected setUp(): void [L53]
  * private setup_scenario(): array [L63]
  * private trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void [L88]
  * public test_auto_enrolment_workflow(): void [L130]
  * public test_plugin_disabled_no_enrolment(): void [L145]
  * public test_user_already_enrolled_no_duplicate(): void [L159]
  * public test_manual_instance_missing_no_enrolment(): void [L179]
  * public test_manual_instance_disabled_no_enrolment(): void [L196]
  * public test_timeend_inherited_from_master(): void [L212]
  * public test_subcourse_without_refcourse_no_enrolment(): void [L234]
  * public test_student_role_fallback_via_archetype(): void [L247]
  * public test_autoenrolled_event_is_fired(): void [L265]
  * public test_master_enrolment_timeend_zero_no_expiry(): void [L320]
  * public test_multiple_active_enrolments_prefers_perpetual(): void [L341]
  * public test_expired_target_enrolment_is_renewed(): void [L387]
  * public test_suspended_target_enrolment_is_not_overridden(): void [L424]
  * public test_future_master_enrolment_does_not_enrol(): void [L457]
  * public test_disabled_master_instance_does_not_enrol(): void [L479]
  * public test_enabled_manual_instance_is_selected(): void [L497]
  * public test_disabled_manual_plugin_does_not_enrol(): void [L533]
  * public test_missing_target_course_does_not_throw(): void [L552]

## `version.php`
