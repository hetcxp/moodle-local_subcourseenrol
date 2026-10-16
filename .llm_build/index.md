# Codebase Contract Index

## `classes/event/user_autoenrolled.php`
- class user_autoenrolled [L34]
  * protected init() [L38]
  * public get_name() [L49]
  * public get_description() [L58]
  * public get_url() [L69]
  * protected validate_data() [L79]

## `classes/observer.php`
- class observer [L34]
  * public reset_caches(): void [L49]
  * private is_enabled(): bool [L60]
  * private get_master_enrolment(int $courseid, int $userid): ?\stdClass [L77]
  * private resolve_enrol_instance(int $courseid): ?\stdClass [L117]
  * private resolve_target_role(): ?\stdClass [L157]
  * private extract_event_payload(\mod_subcourse\event\course_module_viewed $event): ?array [L198]
  * private is_enrolment_eligible(\context_course $coursecontext, int $userid, int $instanceid): bool [L239]
  * private dispatch_enrolment( array $payload, \stdClass $masterenrolment, \stdClass $instance, \stdClass $role ): void [L268]
  * public subcourse_viewed(\mod_subcourse\event\course_module_viewed $event): void [L356]

## `classes/privacy/provider.php`
- class provider [L34]
  * public get_reason(): string [L40]

## `db/access.php`

## `db/events.php`

## `db/upgrade.php`

## `lang/en/local_subcourseenrol.php`

## `lang/es/local_subcourseenrol.php`

## `settings.php`

## `tests/event_test.php`
- class event_test [L36]
  * protected setUp(): void [L41]
  * public test_event_properties(): void [L49]
  * public test_get_name(): void [L78]
  * public test_get_description(): void [L86]
  * public test_get_url(): void [L111]
  * public test_validate_data_missing_mastercourseid(): void [L135]

## `tests/fixtures/test_failing_lock_factory.php`
- class test_failing_lock_factory [L36]
  * public get_lock($resource, $timeout, $maxlifetime = 86400) [L45]

## `tests/observer_test.php`
- class observer_test [L36]
  * public setUpBeforeClass(): void [L41]
  * protected setUp(): void [L52]
  * protected tearDown(): void [L61]
  * private setup_scenario(): array [L71]
  * private get_manual_instance(int $courseid) [L94]
  * private get_user_enrolment(int $enrolid, int $userid) [L106]
  * private get_student_role() [L116]
  * private create_secondary_manual_instance(int $courseid, int $sortorder = 2): \stdClass [L128]
  * private create_subcourse_view_event( $mastercourse, $targetcourse, $student, ?int $refcourse = null ): \mod_subcourse\event\course_module_viewed [L153]
  * private trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void [L202]
  * public test_auto_enrolment_workflow(): void [L210]
  * public test_plugin_disabled_no_enrolment(): void [L223]
  * public test_user_already_enrolled_no_duplicate(): void [L235]
  * public test_manual_instance_missing_no_enrolment(): void [L253]
  * public test_manual_instance_disabled_no_enrolment(): void [L271]
  * public test_timeend_inherited_from_master(): void [L288]
  * public test_subcourse_without_refcourse_no_enrolment(): void [L308]
  * public test_student_role_fallback_via_archetype(): void [L319]
  * public test_autoenrolled_event_is_fired(): void [L335]
  * public test_master_enrolment_timeend_zero_no_expiry(): void [L359]
  * public test_multiple_active_enrolments_prefers_perpetual(): void [L378]
  * public test_expired_target_enrolment_is_renewed(): void [L411]
  * public test_suspended_target_enrolment_is_not_overridden(): void [L446]
  * public test_future_master_enrolment_does_not_enrol(): void [L477]
  * public test_disabled_master_instance_does_not_enrol(): void [L497]
  * public test_enabled_manual_instance_is_selected(): void [L513]
  * public test_disabled_manual_plugin_does_not_enrol(): void [L539]
  * public test_missing_target_course_does_not_throw(): void [L557]
  * public test_guest_user_no_enrolment(): void [L581]
  * public test_invalid_userid_no_enrolment(): void [L594]
  * public test_timestart_exact_boundary_enrolment(): void [L606]
  * public test_repeated_event_does_not_duplicate_enrolment(): void [L622]
  * public test_event_processed_for_different_event_user(): void [L657]
  * public test_configured_role_is_used(): void [L677]
  * public test_configured_role_fallback_when_invalid(): void [L693]
  * public test_lock_failure_handled_gracefully(): void [L710]

## `version.php`
