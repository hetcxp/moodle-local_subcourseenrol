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
            self::markTestSuiteSkipped('mod_subcourse is not installed; skipping all observer tests.');
        }
    }

    /**
     * Setup before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
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
     * Helper to mock the course_module_viewed event.
     *
     * @param \stdClass $mastercourse
     * @param \stdClass $targetcourse
     * @param \stdClass $student
     * @param int|null $refcourse Optional override for subcourse refcourse.
     */
    private function trigger_subcourse_event($mastercourse, $targetcourse, $student, ?int $refcourse = null): void {
        // We mock the event directly to avoid dependency on mod_subcourse generator.
        global $DB;

        $subcourse = new \stdClass();
        $subcourse->course = $mastercourse->id;
        $subcourse->name = 'Mock subcourse';
        $subcourse->refcourse = ($refcourse !== null) ? $refcourse : $targetcourse->id;
        $subcourse->timecreated = time();
        $subcourse->timemodified = time();

        $subcourse->id = $DB->insert_record('subcourse', $subcourse);
        $subcourse = $DB->get_record('subcourse', ['id' => $subcourse->id]);

        $module = $DB->get_record('modules', ['name' => 'subcourse'], 'id', IGNORE_MISSING);
        $moduleid = $module ? $module->id : 1;

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
        $instance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
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
        $instance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timeend', $timeend, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $targetenrolment = $DB->get_record('user_enrolments', ['enrolid' => $targetinstance->id, 'userid' => $student->id]);

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
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $DB->set_field('role', 'shortname', 'student_custom', ['id' => $studentrole->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }

    /**
     * Test user_autoenrolled event is triggered on enrolment.
     */
    public function test_autoenrolled_event_is_fired(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        $subcourse = new \stdClass();
        $subcourse->course = $mastercourse->id;
        $subcourse->name = 'Mock subcourse';
        $subcourse->refcourse = $targetcourse->id;
        $subcourse->timecreated = time();
        $subcourse->timemodified = time();

        $subcourse->id = $DB->insert_record('subcourse', $subcourse);
        $subcourse = $DB->get_record('subcourse', ['id' => $subcourse->id]);

        $module = $DB->get_record('modules', ['name' => 'subcourse'], 'id', IGNORE_MISSING);
        $moduleid = $module ? $module->id : 1;

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
        $instance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $targetenrolment = $DB->get_record('user_enrolments', ['enrolid' => $targetinstance->id, 'userid' => $student->id]);

        $this->assertEquals(0, $targetenrolment->timeend);
    }

    /**
     * Test multiple active enrolments in master prefers perpetual (timeend = 0) over expiring.
     */
    public function test_multiple_active_enrolments_prefers_perpetual(): void {
        global $DB;
        [$mastercourse, $targetcourse, $student] = $this->setup_scenario();

        // Ensure primary manual enrolment has timeend = 0 (perpetual).
        $manualinstance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timeend', 0, ['enrolid' => $manualinstance->id, 'userid' => $student->id]);

        // Add a second enrolment instance directly in DB.
        $secondinstance = (object)[
            'enrol'        => 'manual',
            'status'       => ENROL_INSTANCE_ENABLED,
            'courseid'     => $mastercourse->id,
            'name'         => 'Expiring enrolment',
            'sortorder'    => 2,
            'customint1'   => 0,
            'timecreated'  => time(),
            'timemodified' => time(),
        ];
        $secondinstanceid = $DB->insert_record('enrol', $secondinstance);
        $secondinstance = $DB->get_record('enrol', ['id' => $secondinstanceid]);

        $enrolplugin = enrol_get_plugin('manual');
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $futureexpiry = time() + 7200;
        $enrolplugin->enrol_user($secondinstance, $student->id, $studentrole->id, time(), $futureexpiry);

        // Trigger subcourse event.
        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        // Verify target enrolment inherited the perpetual timeend (0), not the future expiry.
        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $targetenrolment = $DB->get_record('user_enrolments', ['enrolid' => $targetinstance->id, 'userid' => $student->id]);

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

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Create an expired enrolment in target course.
        $pasttime = time() - 3600;
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->enrol_user($targetinstance, $student->id, $studentrole->id, time() - 7200, $pasttime);

        // Master course has a future expiry date.
        $futureexpiry = time() + 86400;
        $masterinstance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
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

        $targetinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

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
        $masterinstance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
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
        $masterinstance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
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
        $firstinstance = $DB->get_record('enrol', ['courseid' => $targetcourse->id, 'enrol' => 'manual']);
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $firstinstance->id]);

        // Create a second enabled manual instance directly in DB with sortorder 2.
        $secondinstance = (object)[
            'enrol'        => 'manual',
            'status'       => ENROL_INSTANCE_ENABLED,
            'courseid'     => $targetcourse->id,
            'name'         => 'Secondary manual instance',
            'sortorder'    => 2,
            'timecreated'  => time(),
            'timemodified' => time(),
        ];
        $secondinstanceid = $DB->insert_record('enrol', $secondinstance);

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
        $instance = $DB->get_record('enrol', ['courseid' => $mastercourse->id, 'enrol' => 'manual']);
        $DB->set_field('user_enrolments', 'timestart', $now, ['enrolid' => $instance->id, 'userid' => $student->id]);

        $this->trigger_subcourse_event($mastercourse, $targetcourse, $student);

        $this->assertTrue(is_enrolled(\context_course::instance($targetcourse->id), $student->id));
    }
}
