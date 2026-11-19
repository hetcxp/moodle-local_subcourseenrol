<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PHPUnit tests for the event observer.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_subcourseenrol;

/**
 * Event observer tests.
 *
 * @package    local_subcourseenrol
 * @category   test
 * @copyright  2026 Héctor Eduardo Terán Canelones
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\local_subcourseenrol\observer::class)]
class observer_test extends \advanced_testcase {
    /**
     * Set up before class.
     */
    public static function setUpBeforeClass(): void {
        global $DB;
        parent::setUpBeforeClass();
        if (!$DB->get_manager()->table_exists('subcourse')) {
            self::markTestSkipped('mod_subcourse is not installed; skipping all observer tests.');
        }
    }

    /**
     * Setup before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        \local_subcourseenrol\observer::reset_caches();
        $this->resetAfterTest();
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void {
        \local_subcourseenrol\observer::reset_caches();
        parent::tearDown();
    }

    /**
     * Helper to set up the scenario.
     *
     * @return array
     */
    private function setup_scenario(): array {
        $mastercourse = $this->getDataGenerator()->create_course();
        $targetcourse = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($student->id, $mastercourse->id, 'student');

        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->add_default_instance($targetcourse);

        set_config('enabled', 1, 'local_subcourseenrol');

        $this->setUser($student);

        return [$mastercourse, $targetcourse, $student];
    }

    /**
     * Helper to fetch manual enrolment instance for a course.
     *
     * @param int $courseid
     * @return \stdClass|false
     */
    private function get_manual_instance(int $courseid) {
        global $DB;
        return $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual']);
    }

    /**
     * Helper to fetch user enrolment for an instance and user.
     *
     * @param int $enrolid
     * @param int $userid
     * @return \stdClass|false
     */
    private function get_user_enrolment(int $enrolid, int $userid) {
        global $DB;
        return $DB->get_record('user_enrolments', ['enrolid' => $enrolid, 'userid' => $userid]);
    }

    /**
     * Helper to fetch standard student role.
     *
     * @return \stdClass|false
     */
    private function get_student_role() {
        global $DB;
        return $DB->get_record('role', ['shortname' => 'student']);
    }

    /**
     * Helper to create a secondary manual enrolment instance.
     *
     * @param int $courseid
     * @param int $sortorder
     * @return \stdClass
     */
    private function create_secondary_manual_instance(int $courseid, int $sortorder = 2): \stdClass {
        global $DB;
        $instance = (object)[
            'enrol'        => 'manual',
            'status'       => ENROL_INSTANCE_ENABLED,
            'courseid'     => $courseid,
            'name'         => 'Secondary manual instance',
            'sortorder'    => $sortorder,
            'customint1'   => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ];
        $instance->id = $DB->insert_record('enrol', $instance);
        return $instance;
    }

    /**
     * Helper to mock and build the course_module_viewed event.
     *
     * @param \stdClass $mastercourse
     * @param \stdClass $targetcourse
     * @param \stdClass $student
     * @param int|null $refcourse Optional override for subcourse refcourse.
     * @return \mod_subcourse\event\course_module_viewed
     */
    private function create_subcourse_view_event(
        $mastercourse,
        $targetcourse,
        $student,
        ?int $refcourse = null
    ): \mod_subcourse\event\course_module_viewed {
        global $DB;

        $subcourse = new \stdClass();
        $subcourse->course = $mastercourse->id;
        $subcourse->name = 'Mock subcourse';
        $subcourse->refcourse = ($refcourse !== null) ? $refcourse : $targetcourse->id;
        $subcourse->timecreated = time();
        $subcourse->timemodified = time();

        $subcourse->id = $DB->insert_record('subcourse', $subcourse);
        $subcourse = $DB->get_record('subcourse', ['id' => $subcourse->id]);

        $module = $DB->get_record('modules', ['name' => 'subcourse'], 'id', MUST_EXIST);
        $moduleid = (int)$module->id;

        $cm = new \stdClass();
        $cm->course = $mastercourse->id;
        $cm->module = $moduleid;
        $cm->instance = $subcourse->id;
        $cm->section = 1;
        $cm->id = $DB->insert_record('course_modules', $cm);

        $context = \context_module::instance($cm->id);

        $event = \mod_subcourse\event\course_module_viewed::create([
            'objectid' => $subcourse->id,
            'context'  => $context,
            'courseid' => $mastercourse->id,
            'userid'   => $student->id,
        ]);

        $event->add_record_snapshot('subcourse', $subcourse);
        return $event;
    }

    /**
     * Helper to mock and trigger the course_module_viewed event.
     *
     * @param \stdClass $mastercourse
     * @param \stdClass $targetcourse
     * @param \stdClass $student
     * @param int|null $refcourse Optional override for subcourse refcourse.
     */
    private function trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void {
        $event = $this->create_subcourse_view_event($mastercourse, $targetcourse, $student, $refcourse);
        $event->trigger();
    }

    /**
     * Test the full auto-enrolment workflow.
     */
    public function test_auto_enrolment_workflow(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test plugin disabled.
     */
    public function test_plugin_disabled_no_enrolment(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        set_config('enabled', 0, 'local_subcourseenrol');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user already enrolled.
     */
    public function test_user_already_enrolled_no_duplicate(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $this->getDataGenerator()->enrol_user($student->id, $targetcourse->id, 'student');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));

        global $DB;
        $instance = $this->get_manual_instance($targetcourse->id);
        $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id, 'userid' => $student->id]);
        $this->assertEquals(1, $count, 'No duplicate user_enrolments records should exist.');
    }

    /**
     * Test missing manual instance.
     */
    public function test_manual_instance_missing_no_enrolment(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Remove manual instance.
        $DB->delete_records('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $expecteddebug = "local_subcourseenrol: No enabled manual enrolment instance found in course " .
            $targetcourse->id . ". Skipping auto-enrolment.";
        $this->assertDebuggingCalled($expecteddebug);
    }

    /**
     * Test disabled manual instance.
     */
    public function test_manual_instance_disabled_no_enrolment(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['courseid' => $targetcourse->id, 'enrol' => 'manual']);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $expecteddebug = "local_subcourseenrol: No enabled manual enrolment instance found in course " .
            $targetcourse->id . ". Skipping auto-enrolment.";
        $this->assertDebuggingCalled($expecteddebug);
    }

    /**
     * Test inherited timeend.
     */
    public function test_timeend_inherited_from_master(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $timeend = time() + 3600;
        // Update master enrolment to have a specific timeend.
        $instance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('user_enrolments', 'timeend', $timeend, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $targetenrolment = $this->get_user_enrolment($targetinstance->id, $student->id);

        $this->assertEquals($timeend, $targetenrolment->timeend);
    }

    /**
     * Test subcourse without refcourse does not trigger enrolment.
     */
    public function test_subcourse_without_refcourse_no_enrolment(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student, 0);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test fallback to archetype roles when 'student' shortname role does not exist.
     */
    public function test_student_role_fallback_via_archetype(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Change shortname of 'student' role to force archetype fallback.
        $studentrole = $this->get_student_role();
        $DB->set_field('role', 'shortname', 'student_custom', ['id' => $studentrole->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user_autoenrolled event is triggered on enrolment.
     */
    public function test_autoenrolled_event_is_fired(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $event = $this->create_subcourse_view_event($mastercourse, $targetcourse, $student);

        $sink = $this->redirectEvents();
        \local_subcourseenrol\observer::subcourse_viewed($event);
        $events = $sink->get_events();
        $sink->close();

        $autoenrolevents = array_filter($events, function ($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });

        $this->assertCount(1, $autoenrolevents);
        $event = reset($autoenrolevents);
        $this->assertEquals($student->id, $event->userid);
        $this->assertEquals($targetcourse->id, $event->courseid);
        $this->assertEquals($mastercourse->id, $event->other['mastercourseid']);
    }

    /**
     * Test master enrolment with timeend = 0 results in target enrolment without expiry.
     */
    public function test_master_enrolment_timeend_zero_no_expiry(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Ensure master enrolment has timeend = 0.
        $instance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $targetenrolment = $this->get_user_enrolment($targetinstance->id, $student->id);

        $this->assertEquals(0, $targetenrolment->timeend);
    }

    /**
     * Test multiple active enrolments in master prefers perpetual (timeend = 0) over expiring.
     */
    public function test_multiple_active_enrolments_prefers_perpetual(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Ensure primary manual enrolment has timeend = 0 (perpetual).
        $manualinstance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $manualinstance->id, 'userid' => $student->id]);

        // Add a second enrolment instance directly in DB.
        $secondinstance = $this->create_secondary_manual_instance($mastercourse->id, 2);

        $enrolplugin = enrol_get_plugin('manual');
        $studentrole = $this->get_student_role();
        $futureexpiry = time() + 7200;
        $enrolplugin->enrol_user($secondinstance, $student->id, $studentrole->id, time(), $futureexpiry);

        // Trigger subcourse event.
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        // Verify target enrolment inherited the perpetual timeend (0), not the future expiry.
        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $targetenrolment = $this->get_user_enrolment($targetinstance->id, $student->id);

        $this->assertEquals(
            0,
            $targetenrolment->timeend,
            'Target enrolment must be perpetual when master has a perpetual enrolment.'
        );
    }

    /**
     * Test that an expired target enrolment is renewed with master timeend.
     */
    public function test_expired_target_enrolment_is_renewed(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $studentrole = $this->get_student_role();

        // Create an expired enrolment in target course.
        $pasttime = time() - 3600;
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->enrol_user($targetinstance, $student->id, $studentrole->id, time() - 7200, $pasttime);

        // Master course has a future expiry date.
        $futureexpiry = time() + 86400;
        $masterinstance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('user_enrolments', 'timeend', $futureexpiry, [
            'enrolid' => $masterinstance->id,
            'userid'  => $student->id,
        ]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetue = $DB->get_records('user_enrolments', [
            'enrolid' => $targetinstance->id,
            'userid'  => $student->id,
        ]);
        $this->assertCount(1, $targetue);
        $ue = reset($targetue);
        $this->assertEquals(ENROL_USER_ACTIVE, (int)$ue->status);
        $this->assertEquals($futureexpiry, (int)$ue->timeend);
    }

    /**
     * Test that an explicitly suspended target enrolment is not overridden.
     */
    public function test_suspended_target_enrolment_is_not_overridden(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $studentrole = $this->get_student_role();

        // Enrol and suspend in target course.
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->enrol_user($targetinstance, $student->id, $studentrole->id, time(), 0, ENROL_USER_SUSPENDED);

        $sink = $this->redirectEvents();
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $events = $sink->get_events();
        $sink->close();

        $ue = $DB->get_record('user_enrolments', [
            'enrolid' => $targetinstance->id,
            'userid'  => $student->id,
        ]);
        $this->assertEquals(ENROL_USER_SUSPENDED, (int)$ue->status);

        $autoenrolevents = array_filter($events, function ($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });
        $this->assertCount(0, $autoenrolevents);
    }

    /**
     * Test that a future master enrolment does not trigger auto-enrolment.
     */
    public function test_future_master_enrolment_does_not_enrol(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Set master enrolment timestart in the future.
        $masterinstance = $this->get_manual_instance($mastercourse->id);
        $futurestart = time() + 3600;
        $DB->set_field('user_enrolments', 'timestart', $futurestart, [
            'enrolid' => $masterinstance->id,
            'userid'  => $student->id,
        ]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test that a disabled master enrolment instance does not trigger auto-enrolment.
     */
    public function test_disabled_master_instance_does_not_enrol(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Disable master enrolment instance.
        $masterinstance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $masterinstance->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test that the enabled manual instance is deterministically selected when multiple exist.
     */
    public function test_enabled_manual_instance_is_selected(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Disable the existing manual instance (sortorder = 1).
        $firstinstance = $this->get_manual_instance($targetcourse->id);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $firstinstance->id]);

        // Create a second enabled manual instance directly in DB with sortorder 2.
        $secondinstance = $this->create_secondary_manual_instance($targetcourse->id, 2);
        $secondinstanceid = $secondinstance->id;

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $targetue = $DB->get_record('user_enrolments', [
            'enrolid' => $secondinstanceid,
            'userid'  => $student->id,
        ]);
        $this->assertNotEmpty($targetue);
        $this->assertEquals($secondinstanceid, (int)$targetue->enrolid);
    }

    /**
     * Test that disabling the manual enrolment plugin globally prevents auto-enrolment.
     */
    public function test_disabled_manual_plugin_does_not_enrol(): void {
        global $CFG;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Remove 'manual' from enabled enrol plugins.
        $enabledplugins = explode(',', $CFG->enrol_plugins_enabled);
        $enabledplugins = array_diff($enabledplugins, ['manual']);
        set_config('enrol_plugins_enabled', implode(',', $enabledplugins));

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $this->assertDebuggingCalled('local_subcourseenrol: Manual enrolment plugin is disabled.');
    }

    /**
     * Test that an invalid or non-existent target course does not throw an exception.
     */
    public function test_missing_target_course_does_not_throw(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Choose a non-existent course id.
        $nonexistentid = 999999;
        while ($DB->record_exists('course', ['id' => $nonexistentid])) {
            $nonexistentid++;
        }

        $sink = $this->redirectEvents();
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student, $nonexistentid);
        $events = $sink->get_events();
        $sink->close();

        $autoenrolevents = array_filter($events, function ($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });
        $this->assertCount(0, $autoenrolevents);
    }

    /**
     * Test that guest user viewing subcourse does not trigger auto-enrolment.
     */
    public function test_guest_user_no_enrolment(): void {
        [$mastercourse, $targetcourse] = $this->setup_scenario();
        $guestuser = guest_user();

        $this->setUser($guestuser);
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $guestuser);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $guestuser->id));
    }

    /**
     * Test that invalid user id (<= 0) viewing subcourse triggers debugging and no enrolment.
     */
    public function test_invalid_userid_no_enrolment(): void {
        [$mastercourse, $targetcourse] = $this->setup_scenario();
        $fakeuser = (object)['id' => 0];

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $fakeuser);

        $this->assertDebuggingCalled('local_subcourseenrol: Invalid userid in subcourse_viewed event.');
    }

    /**
     * Test boundary where master enrolment timestart equals current time exactly.
     */
    public function test_timestart_exact_boundary_enrolment(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $now = time();
        $instance = $this->get_manual_instance($mastercourse->id);
        $DB->set_field('user_enrolments', 'timestart', $now, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test repeated event triggers do not create duplicate enrolments or redundant events.
     */
    public function test_repeated_event_does_not_duplicate_enrolment(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $event = $this->create_subcourse_view_event($mastercourse, $targetcourse, $student);

        $sink = $this->redirectEvents();

        // First trigger: should enrol user and fire event.
        \local_subcourseenrol\observer::subcourse_viewed($event);
        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));

        // Second trigger: already enrolled, should be idempotent and no-op.
        \local_subcourseenrol\observer::subcourse_viewed($event);

        $events = $sink->get_events();
        $sink->close();

        $autoenrolevents = array_filter($events, function ($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });

        $this->assertCount(1, $autoenrolevents, 'Repeated event should not trigger multiple auto-enrolment events.');

        $targetinstance = $this->get_manual_instance($targetcourse->id);
        $count = $DB->count_records('user_enrolments', [
            'enrolid' => $targetinstance->id,
            'userid'  => $student->id,
        ]);
        $this->assertEquals(1, $count, 'User enrolment must remain singular.');
    }

    /**
     * Test that auto-enrolment operates on event userid rather than global USER.
     */
    public function test_event_processed_for_different_event_user(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        $admin = $this->getDataGenerator()->create_user();

        // Set global USER to admin, but event belongs to student.
        $this->setUser($admin);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $admin->id));

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        // Student must be enrolled, admin must not be enrolled.
        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $admin->id));
    }

    /**
     * Test that configured role setting is used for enrolment.
     */
    public function test_configured_role_is_used(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        $targetcontext = \context_course::instance($targetcourse->id);

        $customroleid = $this->getDataGenerator()->create_role();
        set_config('roleid', $customroleid, 'local_subcourseenrol');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled($targetcontext, $student->id));
        $this->assertTrue(user_has_role_assignment($student->id, $customroleid, $targetcontext->id));
    }

    /**
     * Test fallback to default student role when configured role is invalid.
     */
    public function test_configured_role_fallback_when_invalid(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        $targetcontext = \context_course::instance($targetcourse->id);
        $studentrole = $this->get_student_role();

        set_config('roleid', 999999, 'local_subcourseenrol');

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled($targetcontext, $student->id));
        $this->assertTrue(user_has_role_assignment($student->id, $studentrole->id, $targetcontext->id));
        $this->assertDebuggingCalled(
            'local_subcourseenrol: Configured roleid 999999 not found, falling back to default.',
            DEBUG_DEVELOPER
        );
    }

    /**
     * Test lock acquisition contention is handled gracefully without error.
     */
    public function test_lock_failure_handled_gracefully(): void {
        global $CFG;
        require_once(__DIR__ . '/fixtures/test_failing_lock_factory.php');

        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        $targetcontext = \context_course::instance($targetcourse->id);

        $previousfactory = $CFG->lock_factory ?? null;
        $CFG->lock_factory = \local_subcourseenrol\test\test_failing_lock_factory::class;

        // Attempting to trigger event while lock acquisition fails should abort gracefully.
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $this->assertFalse(is_enrolled($targetcontext, $student->id));
        $this->assertDebuggingCalled(
            "local_subcourseenrol: Could not acquire lock for enrol_{$targetcourse->id}_{$student->id}.",
            DEBUG_DEVELOPER
        );

        // Restore lock factory and retry: enrolment should succeed.
        $CFG->lock_factory = $previousfactory;
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $this->assertTrue(is_enrolled($targetcontext, $student->id));
    }

    /**
     * Test that a suspended enrolment in another enrol method (e.g. self) blocks auto-enrolment via manual.
     */
    public function test_suspended_in_other_enrol_method_prevents_autoenrolment(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $selfplugin = enrol_get_plugin('self');
        $selfinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'self']);
        if (!$selfinstance) {
            $selfinstanceid = $selfplugin->add_default_instance($targetcourse);
            $selfinstance = $DB->get_record('enrol', ['id' => $selfinstanceid], '*', MUST_EXIST);
        }

        $studentrole = $this->get_student_role();
        $selfplugin->enrol_user($selfinstance, $student->id, $studentrole->id, 0, 0, ENROL_USER_SUSPENDED);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id, '', true));
        $manualinstance = $this->get_manual_instance($targetcourse->id);
        $this->assertFalse($this->get_user_enrolment($manualinstance->id, $student->id));
    }

    /**
     * Test that an unexpected exception in the observer (e.g. restored event snapshot error) is caught gracefully.
     */
    public function test_missing_subcourse_snapshot_caught_without_exception(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $event = $this->create_subcourse_view_event($mastercourse, $targetcourse, $student);
        // Restoring an event marks it as restored so get_record_snapshot() throws coding_exception.
        $restoredevent = \mod_subcourse\event\course_module_viewed::restore($event->get_data(), []);

        \local_subcourseenrol\observer::subcourse_viewed($restoredevent);

        $this->assertDebuggingCalled(null, DEBUG_DEVELOPER);
        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test that a logged-in-as session skips auto-enrolment and logs a developer notice.
     */
    public function test_loggedinas_session_skips_autoenrolment(): void {
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $this->setAdminUser();
        \core\session\manager::loginas($student->id, \context_system::instance());

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertDebuggingCalled('Auto-enrolment skipped: logged-in-as session.', DEBUG_DEVELOPER);
        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test that globally suspended or deleted user accounts are not auto-enrolled.
     */
    public function test_globally_suspended_and_deleted_users_not_enrolled(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();
        $targetcontext = \context_course::instance($targetcourse->id);

        // Globally suspended user.
        $DB->set_field('user', 'suspended', 1, ['id' => $student->id]);
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);
        $this->assertFalse(is_enrolled($targetcontext, $student->id));

        // Deleted user.
        $deleteduser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($deleteduser->id, $mastercourse->id, 'student');
        $DB->set_field('user', 'deleted', 1, ['id' => $deleteduser->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $deleteduser);
        $this->assertFalse(is_enrolled($targetcontext, $deleteduser->id));
    }

    /**
     * Test that SITEID and self-referencing refcourse values are rejected without enrolment.
     */
    public function test_siteid_and_self_reference_refcourse_rejected(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $sink = $this->redirectEvents();

        // Site frontpage (SITEID) as refcourse.
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student, (int)SITEID);

        // Self-referencing master course as refcourse.
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student, (int)$mastercourse->id);

        $events = $sink->get_events();
        $sink->close();

        $autoenrolevents = array_filter($events, function ($e) {
            return $e instanceof \local_subcourseenrol\event\user_autoenrolled;
        });

        $this->assertCount(0, $autoenrolevents);

        // Verify no user_enrolments record was created on SITEID.
        $siteenrolment = $DB->record_exists_sql(
            "SELECT 1
               FROM {user_enrolments} ue
               JOIN {enrol} e ON e.id = ue.enrolid
              WHERE e.courseid = :courseid
                AND ue.userid = :userid",
            ['courseid' => SITEID, 'userid' => $student->id]
        );
        $this->assertFalse($siteenrolment);
        $this->assertFalse(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }
}
