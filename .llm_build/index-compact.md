# Compact Symbol Map

## `classes/event/user_autoenrolled.php`
- class user_autoenrolled (init, get_name, get_description, get_url, validate_data) [L34]

## `classes/observer.php`
- class observer (reset_caches, is_enabled, get_master_enrolment, resolve_enrol_instance, resolve_target_role, extract_event_payload, is_enrolment_eligible, dispatch_enrolment, subcourse_viewed) [L34]

## `classes/privacy/provider.php`
- class provider (get_reason) [L34]

## `tests/event_test.php`
- class event_test (setUp, test_event_properties, test_get_name, test_get_description, test_get_url, test_validate_data_missing_mastercourseid) [L36]

## `tests/fixtures/test_failing_lock_factory.php`
- class test_failing_lock_factory (get_lock) [L36]

## `tests/observer_test.php`
- class observer_test (setUpBeforeClass, setUp, tearDown, setup_scenario, get_manual_instance, get_user_enrolment, get_student_role, create_secondary_manual_instance, create_subcourse_view_event, trigger_subcourse_event, test_auto_enrolment_workflow, test_plugin_disabled_no_enrolment, test_user_already_enrolled_no_duplicate, test_manual_instance_missing_no_enrolment, test_manual_instance_disabled_no_enrolment, test_timeend_inherited_from_master, test_subcourse_without_refcourse_no_enrolment, test_student_role_fallback_via_archetype, test_autoenrolled_event_is_fired, test_master_enrolment_timeend_zero_no_expiry, test_multiple_active_enrolments_prefers_perpetual, test_expired_target_enrolment_is_renewed, test_suspended_target_enrolment_is_not_overridden, test_future_master_enrolment_does_not_enrol, test_disabled_master_instance_does_not_enrol, test_enabled_manual_instance_is_selected, test_disabled_manual_plugin_does_not_enrol, test_missing_target_course_does_not_throw, test_guest_user_no_enrolment, test_invalid_userid_no_enrolment, test_timestart_exact_boundary_enrolment, test_repeated_event_does_not_duplicate_enrolment, test_event_processed_for_different_event_user, test_configured_role_is_used, test_configured_role_fallback_when_invalid, test_lock_failure_handled_gracefully) [L36]
