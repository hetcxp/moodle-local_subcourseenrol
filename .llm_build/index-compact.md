# Compact Symbol Map

## `classes/event/user_autoenrolled.php`
- class user_autoenrolled (init, get_name, get_description, get_url, validate_data, get_objectid_mapping, get_other_mapping) [L34]

## `classes/local/role_resolver.php`
- class role_resolver (reset_cache, resolve_target_role) [L34]

## `classes/observer.php`
- class observer (reset_caches, is_enabled, get_master_enrolment, resolve_enrol_instance, resolve_target_role, resolve_target_courseid, extract_event_payload, is_enrolment_eligible, dispatch_enrolment, subcourse_viewed) [L34]

## `classes/privacy/provider.php`
- class provider (get_reason) [L34]

## `tests/event_test.php`
- class event_test (setUp, test_event_properties, test_get_name, test_get_description, test_get_url, test_validate_data_missing_mastercourseid, test_get_objectid_mapping, test_get_other_mapping) [L36]

## `tests/fixtures/test_failing_lock_factory.php`
- class test_failing_lock_factory (get_lock) [L36]

## `tests/observer_test.php`
- class observer_test (setUpBeforeClass, setUp, tearDown, setup_scenario, get_manual_instance, get_user_enrolment, get_student_role, create_secondary_manual_instance, create_subcourse_view_event, trigger_subcourse_event, test_auto_enrolment_workflow, test_plugin_disabled_no_enrolment, test_user_already_enrolled_no_duplicate, test_manual_instance_missing_no_enrolment, test_manual_instance_disabled_no_enrolment, test_timeend_inherited_from_master, test_subcourse_without_refcourse_no_enrolment, test_student_role_fallback_via_archetype, test_autoenrolled_event_is_fired, test_master_enrolment_timeend_zero_no_expiry, test_multiple_active_enrolments_prefers_perpetual, test_expired_target_enrolment_is_renewed, test_suspended_target_enrolment_is_not_overridden, test_future_master_enrolment_does_not_enrol, test_disabled_master_instance_does_not_enrol, test_enabled_manual_instance_is_selected, test_disabled_manual_plugin_does_not_enrol, test_missing_target_course_does_not_throw, test_guest_user_no_enrolment, test_invalid_userid_no_enrolment, test_timestart_exact_boundary_enrolment, test_repeated_event_does_not_duplicate_enrolment, test_event_processed_for_different_event_user, test_configured_role_is_used, test_configured_role_fallback_when_invalid, test_lock_failure_handled_gracefully, test_suspended_in_other_enrol_method_prevents_autoenrolment, test_missing_subcourse_snapshot_caught_without_exception, test_loggedinas_session_skips_autoenrolment, test_globally_suspended_and_deleted_users_not_enrolled, test_siteid_and_self_reference_refcourse_rejected) [L36]

## `tests/privacy_provider_test.php`
- class privacy_provider_test (test_null_provider_contract) [L36]

## `tests/role_resolver_test.php`
- class role_resolver_test (setUp, test_resolve_configured_role_assignable_in_course_context, test_resolve_configured_role_not_assignable_in_course_falls_back_to_student, test_resolve_returns_null_with_debugging_when_no_role_available) [L36]
